<?php
declare(strict_types=1);
require dirname(__DIR__, 6).'/vendor/autoload.php';
$app = require dirname(__DIR__, 6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg = config('database.connections.mysql');
if (!in_array($cfg['host'], ['localhost','127.0.0.1','::1'], true)) throw new RuntimeException('Local server only');
$plan = json_decode(file_get_contents(__DIR__.'/plan.json'), true, 512, JSON_THROW_ON_ERROR);
$snapshot = json_decode(file_get_contents(__DIR__.'/'.$plan['snapshot']), true, 512, JSON_THROW_ON_ERROR);
$scratch = 'avia_letter_audit_20260921_'.bin2hex(random_bytes(4));
$server = Illuminate\Support\Facades\DB::connection()->getPdo();
$server->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo = new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port'] ?? 3306).';dbname='.$scratch.';charset=utf8mb4', $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>true, PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
config(['database.connections.mysql.database'=>$scratch]);
Illuminate\Support\Facades\DB::purge('mysql');
$tables = ['manuals','components','manual_part_groups','manual_part_group_options','manual_part_group_coverages','log_cards','tdrs'];
$referenceCols = [];
foreach (array_keys($snapshot['references']) as $key) {
    [$table,$col] = explode('.', $key); $referenceCols[$table][] = $col; $tables[] = $table;
}
$tables = array_values(array_unique($tables));
function must(bool $ok, string $why): void { if (!$ok) throw new RuntimeException($why); }
function seed(PDO $pdo, string $table, array $rows): void {
    foreach ($rows as $r) {
        $pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($r)).'`) VALUES ('.implode(',',array_fill(0,count($r),'?')).')')->execute(array_values($r));
    }
}
function fixture(PDO $pdo, array $snapshot, array $referenceCols): void {
    must((bool)preg_match('/^avia_letter_audit_20260921_[a-f0-9]{8}$/', $pdo->query('SELECT DATABASE()')->fetchColumn()), 'Not isolated scratch');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS manuals');
    $pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),title VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
    foreach ($snapshot['schema'] as $table=>$schema) {
        $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
        $ddl = preg_replace('/^  CONSTRAINT[^\n]+\n?/m', '', $schema[0]['Create Table']);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl); $pdo->exec($ddl);
    }
    foreach ($referenceCols as $table=>$cols) {
        $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
        $pdo->exec('CREATE TABLE `'.$table.'` (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,'.implode(',', array_map(fn($c)=>'`'.$c.'` BIGINT UNSIGNED NULL', $cols)).') ENGINE=InnoDB');
    }
    $pdo->exec('DROP TABLE IF EXISTS log_cards');
    $pdo->exec('DROP TABLE IF EXISTS tdrs');
    $pdo->exec('CREATE TABLE tdrs(id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,workorder_id BIGINT UNSIGNED,order_component_id BIGINT UNSIGNED) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE log_cards(id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, component_data LONGTEXT NULL,component_data_out LONGTEXT NULL,destruction_certificate_data LONGTEXT NULL) ENGINE=InnoDB');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->beginTransaction();
    foreach (['manuals'=>'manuals','components'=>'components','manual_part_groups'=>'groups','manual_part_group_options'=>'options','manual_part_group_coverages'=>'coverages'] as $table=>$key) seed($pdo,$table,$snapshot[$key]);
    // Deliberate non-empty process flags prove the migration preserves them.
    $pdo->exec('UPDATE components SET log_card=1,ndt_list=1,cad_list=1,kit=1,paint_list=1 WHERE id='.$snapshot['components'][0]['id']);
    $pdo->commit();
}
function raw(PDO $pdo, string $sql): array {
    $s=$pdo->query($sql); $rows=[];
    do { if ($s->columnCount()>0) $rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC)); } while ($s->nextRowset());
    $s->closeCursor(); return $rows;
}
function status(array $rows): string {
    foreach ($rows as $row) if (isset($row['final_status'])) return $row['final_status'];
    return 'NONE';
}
function digest(PDO $pdo, array $tables): string {
    $rows=[]; foreach ($tables as $table) $rows[$table]=$pdo->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    return hash('sha256',json_encode($rows));
}
function graphAudit(array $manualIds, array $parentIds): array {
    $wo=new class extends App\Models\Workorder { public array $auditManualIds=[]; public function usedManualIds(): array { return $this->auditManualIds; } };
    $wo->auditManualIds=$manualIds;
    $resolver=new App\Services\PartGroupCoverageResolver();
    $groups=App\Models\ManualPartGroup::whereIn('manual_id',$manualIds)->with('options.coverages')->get()->keyBy('id');
    $options=$groups->flatMap(fn($g)=>$g->options)->keyBy('id');
    $familyMethod=new ReflectionMethod($resolver,'bundleIplFamilies');$familyMethod->setAccessible(true);
    $familyMap=$familyMethod->invoke($resolver,$wo,$groups);
    $members=new ReflectionMethod($resolver,'bundleMembers');$members->setAccessible(true);
    $expand=new ReflectionMethod($resolver,'expandBundleMember');$expand->setAccessible(true);
    $scoper=new App\Services\WorkorderPartScopeResolver();
    $scopeMethod=new ReflectionMethod($scoper,'optionComponentQuantities');$scopeMethod->setAccessible(true);
    $out=['coverage'=>[],'scope'=>[],'log_card'=>[]];
    $composition=app(App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($groups);
    foreach ($groups->where('behavior', 'bundle') as $g) {
        $out['log_card'][$g->id]=$composition[$g->id]->sort()->values()->all();
    }
    foreach ($groups as $g) {
        if ($g->behavior!=='bundle') continue;
        foreach ($g->options as $o) foreach (['prl','ndt','cad','stress','paint'] as $scope) {
            if (!$g->appliesTo($scope)) continue;
            $cov=[];
            foreach ($members->invoke($resolver,$o,$scope,$familyMap) as $member) {
                $args=[&$cov,$member,2,$scope,'audit',$g,$o,$groups,$options,[$o->id=>true],$familyMap];
                $expand->invokeArgs($resolver,$args);
            }
            $q=array_map(fn($x)=>$x['covered_qty'],$cov);ksort($q);
            $out['coverage'][$o->id.'|'.$scope]=hash('sha256',json_encode($q));
            if (in_array($o->id,$parentIds,true)) {
                $q=$scopeMethod->invoke($scoper,$o->id,$scope);ksort($q);
                $out['scope'][$o->id.'|'.$scope]=hash('sha256',json_encode($q));
            }
        }
    }
    return $out;
}
$prefix=__DIR__.'/ipl_letter_families_20260921_v01_';
$sql=file_get_contents($prefix.'import.sql');$verify=file_get_contents($prefix.'verify.sql');$rollback=file_get_contents($prefix.'rollback.sql');
$parents=[];foreach($snapshot['coverages'] as $e) if(in_array($e['id'],$plan['edges'],true)) $parents[]=(int)$e['manual_part_group_option_id'];
$report=['production_modified'=>false,'working_database_modified'=>false,'snapshot'=>$plan['snapshot'],'sql_sha256'=>hash('sha256',$sql)];
try {
    fixture($pdo,$snapshot,$referenceCols);$original=digest($pdo,$tables);
    must(status(raw($pdo,$sql))==='SUCCESS', 'Raw-file smoke import failed');
    must(status(raw($pdo,$rollback))==='SUCCESS'&&$original===digest($pdo,$tables), 'Raw-file smoke rollback failed');
    echo 'Raw-file smoke import and rollback PASS'.PHP_EOL;
    $beforeGraph=graphAudit($plan['manuals'],array_unique($parents));
    echo 'Baseline graph captured'.PHP_EOL;
    $first=raw($pdo,$sql);
    must(status($first)==='SUCCESS','Initial SQL failed: '.json_encode($first));
    foreach(raw($pdo,$verify) as $row) must($row['result']==='OK','SELECT audit mismatch: '.$row['check_name']);
    $afterGraph=graphAudit($plan['manuals'],array_unique($parents));
    must($beforeGraph===$afterGraph,'Coverage or Work Scope changed after retirement');
    $report['behavior']=['coverage_cases'=>count($beforeGraph['coverage']),'work_scope_cases'=>count($beforeGraph['scope']),'log_card_groups'=>count($beforeGraph['log_card']),'identical'=>true];
    echo 'Import, SELECT verification and graph equivalence PASS'.PHP_EOL;
    foreach(['groups'=>'manual_part_groups','options'=>'manual_part_group_options','coverages'=>'manual_part_group_coverages'] as $key=>$table) {
        $actual=$pdo->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        must(json_encode($actual)===json_encode($plan['expected'][$key]), 'Exact expected rows differ: '.$table);
    }
    $final=digest($pdo,$tables);
    must(status(raw($pdo,$sql))==='SUCCESS'&&$final===digest($pdo,$tables),'Repeat changed data');
    must(status(raw($pdo,$rollback))==='SUCCESS'&&$original===digest($pdo,$tables),'Rollback did not restore exact data');
    must(status(raw($pdo,$rollback))==='SUCCESS'&&$original===digest($pdo,$tables),'Repeat rollback changed data');
    $report['positive']=['raw_file_with_comments'=>'PASS','exact_rows'=>'PASS','repeat'=>'PASS','rollback'=>'PASS','repeat_rollback'=>'PASS'];
    $edge=$plan['edges'][0];$group=$plan['groups'][0];
    $opt=array_values(array_filter($snapshot['options'],fn($o)=>$o['manual_part_group_id']===$group))[0]['id'];
    $familyOptions=array_values(array_filter($snapshot['options'],fn($o)=>$o['manual_part_group_id']===$group));
    $mutations=[
        'changed_quantity'=>'UPDATE manual_part_group_coverages SET qty=qty+1 WHERE id='.$edge,
        'changed_manual'=>"UPDATE manuals SET number='WRONG' WHERE id=".$plan['manuals'][0],
        'new_history_reference'=>'INSERT INTO workorder_part_group_selections(manual_part_group_id,manual_part_group_option_id) VALUES('.$group.','.$opt.')',
        'log_card_reference'=>"INSERT INTO log_cards(component_data) VALUES ('[{\"manual_part_group_id\":\"".$group."\"}]')",
        'new_part'=>"INSERT INTO components(manual_id,ipl_num,part_number,name) VALUES (".$plan['manuals'][0].",'999-123','NEW','New after audit')",
        'multiple_ordered_variants'=>'INSERT INTO tdrs(workorder_id,order_component_id) VALUES (1,'.$familyOptions[0]['component_id'].'),(1,'.$familyOptions[1]['component_id'].')',
    ];
    foreach($mutations as $name=>$mutation) {
        fixture($pdo,$snapshot,$referenceCols);$pdo->exec($mutation);$before=digest($pdo,$tables);
        must(status(raw($pdo,$sql))==='BLOCKED / ROLLED BACK'&&$before===digest($pdo,$tables),'Unsafe negative case: '.$name);
        $report['negative'][$name]='BLOCKED, unchanged';echo $name.' PASS'.PHP_EOL;
    }
    fixture($pdo,$snapshot,$referenceCols);$before=digest($pdo,$tables);
    $omitted=preg_replace('/WHERE @lf_apply AND id='.$edge.';/', 'WHERE @lf_apply AND id='.$edge.' AND 0;', $sql,1);
    must($omitted!==$sql,'Failure injection missed');
    must(status(raw($pdo,$omitted))==='BLOCKED / ROLLED BACK'&&$before===digest($pdo,$tables),'Partial updates not rolled back');
    $report['negative']['missed_update']='ROLLED BACK, unchanged';
    $report['result']='PASS';file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Illuminate\Support\Facades\DB::disconnect('mysql');
    if(preg_match('/^avia_letter_audit_20260921_[a-f0-9]{8}$/',$scratch)) $server->exec('DROP DATABASE `'.$scratch.'`');
}
