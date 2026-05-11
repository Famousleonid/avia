<?php

declare(strict_types=1);

if (($argc ?? 0) < 3) {
    fwrite(STDERR, "Usage: php database/scripts/inspect-components-dump-row.php <sanitized.sql> <1-based-row-number>\n");
    exit(1);
}

$path = $argv[1];
$rowNumber = max(1, (int) $argv[2]);

function splitSqlTupleInspect(string $tuple): array
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

$lines = file($path);
$line = $lines[221] ?? null;
if ($line === null) {
    fwrite(STDERR, "Components INSERT line not found.\n");
    exit(1);
}

$prefix = 'INSERT INTO `components` (`id`,`part_number`,`assy_part_number`,`name`,`ipl_num`,`assy_ipl_num`,`eff_code`,`units_assy`,`log_card`,`is_bush`,`paint_list`,`stress_relief_list`,`cad_list`,`ndt_list`,`kit`,`bush_ipl_num`,`manual_id`,`created_at`,`updated_at`,`deleted_at`) VALUES ';
$body = substr(rtrim($line, "\r\n;"), strlen($prefix));
$tuples = preg_split('/\),\(/', substr($body, 1, -1));
$tuple = $tuples[$rowNumber - 1] ?? null;

if ($tuple === null) {
    fwrite(STDERR, "Tuple #{$rowNumber} not found.\n");
    exit(1);
}

$values = splitSqlTupleInspect($tuple);
echo 'count=', count($values), PHP_EOL;
echo $tuple, PHP_EOL;
