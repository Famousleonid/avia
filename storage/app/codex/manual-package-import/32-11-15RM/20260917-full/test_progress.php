<?php
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
foreach($pdo->query('SHOW FULL PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC) as $p){
 if(preg_match('/^avia_m78_review_20260917_[a-f0-9]{8}$/',(string)$p['db'])){
  echo json_encode(['id'=>$p['Id'],'db'=>$p['db'],'seconds'=>$p['Time'],'state'=>$p['State'],'query_start'=>substr((string)$p['Info'],0,150)]).PHP_EOL;
  if(in_array('--cancel-own-verification',$argv,true)&&str_starts_with((string)$p['Info'],'WITH avia_m78_parts AS')){$pdo->exec('KILL QUERY '.(int)$p['Id']);}
 }
}
