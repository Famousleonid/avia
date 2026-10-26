<?php
require dirname(__DIR__,6).'/vendor/autoload.php';$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo json_encode(Illuminate\Support\Facades\DB::select("SELECT ID,DB,COMMAND,TIME,STATE,LEFT(INFO,180) AS own_test_query FROM information_schema.PROCESSLIST WHERE DB LIKE 'avia_m28_review_%'"),JSON_PRETTY_PRINT);
$schemas=Illuminate\Support\Facades\DB::select("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'avia_m28_review_%'");foreach($schemas as $s){$n=$s->SCHEMA_NAME;if(!preg_match('/^avia_m28_review_[0-9a-f]{8}$/',$n))continue;echo json_encode(Illuminate\Support\Facades\DB::select('SELECT COUNT(*) AS committed_parts FROM `'.$n.'`.components'),JSON_PRETTY_PRINT);}
