<?php

declare(strict_types=1);

if (($argc ?? 0) < 3) {
    fwrite(STDERR, "Usage: php database/scripts/sanitize-components-generated-column-dump.php <source.sql> <dest.sql>\n");
    exit(1);
}

$source = $argv[1];
$dest = $argv[2];

$in = fopen($source, 'rb');
$out = fopen($dest, 'wb');

if ($in === false || $out === false) {
    fwrite(STDERR, "Cannot open sanitize streams.\n");
    exit(1);
}

function splitSqlTuple(string $tuple): array
{
    $values = [];
    $current = '';
    $inString = false;
    $length = strlen($tuple);

    for ($i = 0; $i < $length; $i++) {
        $ch = $tuple[$i];

        if ($inString && $ch === '\\' && $i + 1 < $length) {
            $current .= $ch . $tuple[$i + 1];
            $i++;
            continue;
        }

        if ($ch === "'") {
            $current .= $ch;

            if ($inString && $i + 1 < $length && $tuple[$i + 1] === "'") {
                $current .= "'";
                $i++;
                continue;
            }

            $inString = ! $inString;
            continue;
        }

        if ($ch === ',' && ! $inString) {
            $values[] = $current;
            $current = '';
            continue;
        }

        $current .= $ch;
    }

    $values[] = $current;

    return $values;
}

while (($line = fgets($in)) !== false) {
    if (strncmp($line, 'INSERT INTO `components` VALUES ', 32) !== 0) {
        fwrite($out, $line);
        continue;
    }

    $sourcePrefix = 'INSERT INTO `components` VALUES ';
    $prefix = 'INSERT INTO `components` (`id`,`part_number`,`assy_part_number`,`name`,`ipl_num`,`assy_ipl_num`,`eff_code`,`units_assy`,`log_card`,`is_bush`,`paint_list`,`stress_relief_list`,`cad_list`,`ndt_list`,`kit`,`bush_ipl_num`,`manual_id`,`created_at`,`updated_at`,`deleted_at`) VALUES ';
    $body = substr(rtrim($line, "\r\n;"), strlen($sourcePrefix));
    $tuples = preg_split('/\),\(/', substr($body, 1, -1));
    $rebuilt = [];

    foreach ($tuples as $tuple) {
        $values = splitSqlTuple($tuple);
        if (count($values) !== 21 && count($values) !== 20) {
            fwrite(STDERR, 'Unexpected components tuple width: '.count($values)."\n");
            fwrite(STDERR, substr($tuple, 0, 400)."\n");
            exit(1);
        }

        if (count($values) === 21) {
            unset($values[5]);
        }

        $rebuilt[] = '('.implode(',', array_values($values)).')';
    }

    fwrite($out, $prefix.implode(',', $rebuilt).";\n");
}

fclose($in);
fclose($out);
