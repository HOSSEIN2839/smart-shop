<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Models\PriceCalculator;
use App\Support\Text;

$file = fopen(__DIR__ . '/../tests/price', 'r');

if ($file === false) {
    fwrite(STDERR, "FAIL: Cannot open tests/price\n");
    exit(1);
}

$header = fgetcsv($file);
$expectedHeader = [
    'current_price',
    'sale_price',
    'regular_price',
    'on_sale',
    'expected_final',
    'expected_discount',
];

if ($header !== $expectedHeader) {
    fwrite(STDERR, "FAIL: Unexpected CSV header\n");
    fclose($file);
    exit(1);
}

$row = 0;
$fails = 0;

while (($data = fgetcsv($file)) !== false) {
    $row++;

    if (count($data) !== 6) {
        $fails++;
        echo "Row $row: FAIL (invalid column count)\n";
        continue;
    }

    $current = $data[0] === ''
        ? null : (int) round((float) $data[0]);

    $sale = $data[1] === ''
        ? null : (int) round((float) $data[1]);

    $regular = (int) round((float) $data[2]);
    $onSale = (int) $data[3] === 1;
    $expectedFinal = (int) $data[4];
    $expectedDiscount = (int) $data[5];

    $actualFinal = PriceCalculator::finalPrice(
        $current,
        $sale,
        $regular,
        $onSale
    );

    $actualDiscount = PriceCalculator::discountPercent(
        $regular,
        $sale,
        $onSale
    );

    if (
        $actualFinal === $expectedFinal &&
        $actualDiscount === $expectedDiscount
    ) {
        echo "Row $row: OK\n";
    } else {
        $fails++;
        echo "Row $row: FAIL"
            . " (final expected=$expectedFinal actual=$actualFinal"
            . "; discount expected=$expectedDiscount actual=$actualDiscount)\n";
    }
}

fclose($file);

$formatOk = Text::priceText(13650000) === '۱۳٬۶۵۰٬۰۰۰ تومان';

echo $formatOk
    ? "Price text formatting: OK\n"
    : "Price text formatting: FAIL\n";

if (!$formatOk) {
    $fails++;
}

echo "$row rows checked; " . ($row - $fails) . " OK; $fails FAIL\n";

exit($fails === 0 && $row === 10 ? 0 : 1);
