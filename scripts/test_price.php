<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Models\PriceCalculator;

$file = fopen(__DIR__ . '/../tests/price', 'r');
if ($file === false) {
    fwrite(STDERR, "FAIL: Cannot open tests/price\n");
    exit(1);
}

fgetcsv($file);
$row = 0;
$fails = 0;

while (($data = fgetcsv($file)) !== false) {
    if (count($data) < 3) {
        $row++;
        $fails++;
        echo "Row $row: FAIL (invalid data)\n";
        continue;
    }

    [$base, $discount, $expected] = array_map('intval', $data);
    $actual = PriceCalculator::finalPrice($base, $discount);
    $row++;

    if ($actual === $expected) {
        echo "Row $row: OK\n";
    } else {
        $fails++;
        echo "Row $row: FAIL (expected $expected, got $actual)\n";
    }
}

fclose($file);
echo "$row rows checked; " . ($row - $fails) . " OK; $fails FAIL\n";
exit($fails === 0 && $row === 10 ? 0 : 1);
