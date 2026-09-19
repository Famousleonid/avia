<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

// Resolve test output relative to this checkout, before Laravel boots views.
$testViewPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'codex-test-runtime'.DIRECTORY_SEPARATOR.'views';
if (! is_dir($testViewPath) && ! mkdir($testViewPath, 0777, true) && ! is_dir($testViewPath)) {
    throw new RuntimeException('Unable to create the test view directory.');
}
$_ENV['VIEW_COMPILED_PATH'] = $testViewPath;
$_SERVER['VIEW_COMPILED_PATH'] = $testViewPath;
putenv('VIEW_COMPILED_PATH='.$testViewPath);
