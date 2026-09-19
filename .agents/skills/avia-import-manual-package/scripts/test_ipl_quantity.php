<?php

declare(strict_types=1);

require_once __DIR__.'/ipl_quantity.php';

$cases = [[null, '1'], ['', '1'], ['  ', '1'], ['AR', '1'], ['RF', '1'], [' ref ', '1'], ['2A', '1'], ['2', '2'], [' 12 ', '12'], ['0', '0'], ['2.5', '2.5'], ['2,5', '2.5']];
foreach ($cases as [$input, $expected]) {
    if (normalizeIplQuantity($input) !== $expected) {
        throw new RuntimeException('Quantity normalization failed: '.var_export($input, true));
    }
}
foreach (['?', '-', '2/3', '2 4'] as $input) {
    try {
        normalizeIplQuantity($input);
    } catch (InvalidArgumentException $e) {
        continue;
    }
    throw new RuntimeException('Ambiguous quantity was not rejected: '.$input);
}
echo "PASS: 12 normalization cases and 4 ambiguous quantity guards.\n";
