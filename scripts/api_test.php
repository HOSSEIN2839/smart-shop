<?php

$url = 'https://zarifwatch.ir/wp-json/wc/store/v1/products?per_page=5';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);

$response = curl_exec($ch);

if ($response === false) {
    fwrite(STDERR, 'cURL error: ' . curl_error($ch) . PHP_EOL);
    curl_close($ch);
    exit(1);
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status < 200 || $status >= 300) {
    fwrite(STDERR, "HTTP error: $status" . PHP_EOL);
    exit(1);
}

$products = json_decode($response, true);

if (!is_array($products)) {
    fwrite(STDERR, "Invalid JSON response." . PHP_EOL);
    exit(1);
}

$count = 0;

foreach ($products as $product) {
    if ($count >= 5) {
        break;
    }

    $id = $product['id'] ?? 'unknown';
    $name = $product['name'] ?? 'Unnamed product';
    $prices = $product['prices'] ?? [];
    $rawPrice = $prices['price'] ?? null;
    $minorUnit = (int) ($prices['currency_minor_unit'] ?? 2);
    $symbol = $prices['currency_symbol'] ?? '';

    if (is_numeric($rawPrice)) {
        $price = number_format(
            (float) $rawPrice / (10 ** $minorUnit),
            $minorUnit,
            '.',
            ','
        ) . ' ' . $symbol;
    } else {
        $price = 'Unavailable';
    }

    echo "ID: $id | Name: $name | Price: $price" . PHP_EOL;
    $count++;
}

if ($count === 0) {
    echo "No products returned by the API." . PHP_EOL;
}