<?php
require_once __DIR__ . '/../app/helpers/price.php';

$file = fopen(__DIR__ . '/../tests/price_tests.csv', 'r');
fgetcsv($file, 0, ',', '"', '');

$row = 0;
$ok = 0;
while (($r = fgetcsv($file, 0, ',', '"', '')) !== false) {
    $row++;
    $got = final_price((float)$r[0], (float)$r[1]);
    if ($got === (int)$r[2]) {
        $ok++;
        echo "Row $row: OK\n";
    } else {
        echo "Row $row: FAIL (expected $r[2], got $got)\n";
    }
}
fclose($file);
echo "$ok of $row OK\n";