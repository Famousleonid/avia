<?php
/**
 * Compares App\Http\Controllers\* public methods with php artisan route:list --json actions.
 * Prints methods that never appear as Controller@method (heuristic — not 100%: __invoke, strings in config).
 */
$root = dirname(__DIR__);
chdir($root);

exec('php artisan route:list --json 2>NUL', $out, $code);
if ($code !== 0 || empty($out)) {
    fwrite(STDERR, "route:list failed\n");
    exit(1);
}
$json = json_decode(implode("\n", $out), true);
if (!is_array($json)) {
    fwrite(STDERR, "invalid json\n");
    exit(1);
}

$routed = [];
foreach ($json as $row) {
    $action = $row['action'] ?? '';
    if (preg_match('/^App\\\\Http\\\\Controllers\\\\(.+?)@(\w+)$/', $action, $m)) {
        $routed[$m[1]][$m[2]] = true;
    }
    // Also MainController@x referenced from TdrProcessController routes
    if (preg_match('/^App\\\\Http\\\\Controllers\\\\(.+)$/', $action, $m) && !str_contains($action, '@')) {
        // single action class — __invoke
        $routed[$m[1]]['__invoke'] = true;
    }
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/app/Http/Controllers', RecursiveDirectoryIterator::SKIP_DOTS)
);
$unrouted = [];
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $content = file_get_contents($path);
    if (!preg_match('/^namespace\s+(.+?);/m', $content, $nm)) {
        continue;
    }
    $ns = trim($nm[1]);
    if (!str_starts_with($ns, 'App\\Http\\Controllers\\')) {
        continue;
    }
    $base = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR;
    $rel = substr($path, strlen($base));
    $rel = str_replace(['/', '\\'], '\\', $rel);
    $rel = preg_replace('/\.php$/', '', $rel);
    $fqcn = 'App\\Http\\Controllers\\' . $rel;

    if (!preg_match_all('/^\s*public\s+function\s+(\w+)\s*\(/m', $content, $matches)) {
        continue;
    }
    foreach ($matches[1] as $method) {
        if (in_array($method, ['__construct', 'middleware', 'getMiddleware', 'callAction', 'authorize', 'validate'], true)) {
            continue;
        }
        $shortClass = substr($fqcn, strlen('App\\Http\\Controllers\\'));
        if (empty($routed[$shortClass][$method])) {
            $unrouted[] = "$fqcn::$method";
        }
    }
}

sort($unrouted);
foreach ($unrouted as $line) {
    echo $line, "\n";
}
