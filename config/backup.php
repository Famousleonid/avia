<?php

return [

    /*
    |--------------------------------------------------------------------------
    | mysqldump / mysql client
    |--------------------------------------------------------------------------
    | On Windows (OSPanel) set full path, e.g.:
    | C:\OSPanel\modules\database\MySQL-8.0\bin\mysqldump.exe
    */
    'mysqldump_binary' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql_binary' => env('BACKUP_MYSQL_PATH', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Storage (under storage/app)
    |--------------------------------------------------------------------------
    */
    'directory' => 'backups',

    /*
    |--------------------------------------------------------------------------
    | Retention: delete *.sql.gz older than N days (0 = never)
    |--------------------------------------------------------------------------
    */
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 5),

];
