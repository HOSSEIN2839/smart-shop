<?php
declare(strict_types=1);

mb_internal_encoding('UTF-8');

const API_BASE      = 'https://zarifwatch.ir/wp-json/wc/store/v1/products';
const IDS_FILE      = __DIR__ . '/../database/selected_ids.txt';
const SNAPSHOT_FILE = __DIR__ . '/../database/products_snapshot.json';

/**
 * خواندن شناسه‌های انتخاب‌شده از فایل متنی.
 */
function read_selected_ids(string $path): array
{
    if (!is_file($path)) {
        fwrite(STDERR, "خطا: فایل شناسه‌ها پیدا نشد: {$path}\n");
        exit(1);
    }

    $ids = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line, " \t\r\n\xEF\xBB\xBF");
        if ($line !== '' && ctype_digit($line)) {
            $id = (int)$line;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }

    return array_values(array_unique($ids));
}

/**
 * دریافت محتوای یک نشانی از API.
 */
function http_get(string $url): string
{
    $headers = [
        'Accept: application/json',
        'User-Agent: smart-shop-import/2.0',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 300) {
            throw new RuntimeException("خطا در دریافت API؛ HTTP {$code}. {$error}");
        }

        return (string)$body;
    }

    $context = stream_context_create([
        'http' => [
            'header' => implode("\r\n", $headers),
            'timeout' => 60,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        throw new RuntimeException('خطا در دریافت اطلاعات از API.');
    }

    return $body;
}

/**
 * دریافت محصولات بر اساس شناسه‌های selected_ids.txt.
 */
function fetch_products(array $ids): array
{
    $all = [];

    foreach (array_chunk($ids, 100) as $chunk) {
        $url = API_BASE . '?per_page=100&include=' . implode(',', $chunk);
        $decoded = json_decode(http_get($url), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('پاسخ API معتبر نیست یا JSON نیست.');
        }

        $all = array_merge($all, $decoded);
    }

    return $all;
}

/**
 * تبدیل قیمت API به عدد صحیح تومان.
 * در API فروشگاه، currency_minor_unit واحد اعشار را مشخص می‌کند.
 */
function api_price($value, array $prices): ?int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }

    $minorUnit = max(0, min(6, (int)($prices['currency_minor_unit'] ?? 0)));
    $amount = ((float)$value) / (10 ** $minorUnit);

    if (!is_finite($amount) || $amount < 0 || $amount > PHP_INT_MAX) {
        return null;
    }

    return (int)round($amount);
}

function html_to_text(string $html): string
{
    $html = preg_replace('~</\s*(p|div|h[1-6]|li)\s*>~i', "$0\n", $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xC2\xA0", ' ', $text);
    $text = preg_replace('~[ \t]+~u', ' ', $text);
    $text = preg_replace('~\s*\n\s*~u', "\n", $text);

    return trim($text);
}

function clean_name(string $value): string
{
    return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * انتخاب یک دسته‌بندی مطابق اولویت‌های پروژه.
 */
function pick_category(array $categories): string
{
    $preferred = ['ساعت مردانه', 'ساعت زنانه', 'ساعت رومیزی'];
    $names = [];

    foreach ($categories as $category) {
        if (is_array($category)) {
            $name = clean_name((string)($category['name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
    }

    foreach ($preferred as $name) {
        if (in_array($name, $names, true)) {
            return $name;
        }
    }

    return $names[0] ?? 'بدون دسته‌بندی';
}

/**
 * ساخت متن ویژگی‌ها از ویژگی‌ها و برچسب‌های محصول.
 */
function make_features(array $raw): ?string
{
    $lines = [];

    $attributes = is_array($raw['attributes'] ?? null) ? $raw['attributes'] : [];
    foreach ($attributes as $attribute) {
        if (!is_array($attribute)) {
            continue;
        }

        $name = clean_name((string)($attribute['name'] ?? ''));
        $values = $attribute['terms'] ?? $attribute['options'] ?? [];

        if (!is_array($values)) {
            $values = [$values];
        }

        $cleanValues = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $value = $value['name'] ?? $value['value'] ?? '';
            }
            $value = clean_name((string)$value);
            if ($value !== '') {
                $cleanValues[] = $value;
            }
        }

        if ($name !== '' && $cleanValues) {
            $lines[] = $name . ': ' . implode('، ', array_unique($cleanValues));
        }
    }

    $tags = is_array($raw['tags'] ?? null) ? $raw['tags'] : [];
    $tagNames = [];
    foreach ($tags as $tag) {
        if (is_array($tag)) {
            $tagName = clean_name((string)($tag['name'] ?? ''));
            if ($tagName !== '') {
                $tagNames[] = $tagName;
            }
        }
    }

    if ($tagNames) {
        $lines[] = 'برچسب‌ها: ' . implode('، ', array_unique($tagNames));
    }

    $features = trim(implode("\n", $lines));
    return $features !== '' ? $features : null;
}

/**
 * تبدیل محصول خام API به فیلدهای جدول products در schema.sql.
 *
 * نگاشت:
 * API id             -> products.woo_id
 * API regular_price  -> products.base_price
 * قیمت فعلی در API   -> محاسبه discount_percent
 * API is_in_stock    -> products.in_stock
 */
function clean_product(array $raw): array
{
    $prices = is_array($raw['prices'] ?? null) ? $raw['prices'] : [];

    $regular = api_price(
        $prices['regular_price'] ?? $prices['price'] ?? null,
        $prices
    );

    $current = api_price(
        $prices['price'] ?? $prices['sale_price'] ?? $prices['regular_price'] ?? null,
        $prices
    );

    $discount = 0;
    if ($regular !== null && $regular > 0 && $current !== null && $current < $regular) {
        $discount = (int)round((($regular - $current) / $regular) * 100);
        $discount = max(0, min(100, $discount));
    }

    $brands = is_array($raw['brands'] ?? null) ? $raw['brands'] : [];
    $brandName = '';
    if (isset($brands[0]) && is_array($brands[0])) {
        $brandName = clean_name((string)($brands[0]['name'] ?? ''));
    }

    $images = is_array($raw['images'] ?? null) ? $raw['images'] : [];
    $imageUrl = '';
    if (isset($images[0]) && is_array($images[0])) {
        $imageUrl = trim((string)($images[0]['src'] ?? ''));
    }

    return [
        'woo_id' => (int)($raw['id'] ?? 0),
        'name' => clean_name((string)($raw['name'] ?? '')),
        'brand' => $brandName !== '' ? $brandName : 'نامشخص',
        'category' => pick_category(is_array($raw['categories'] ?? null) ? $raw['categories'] : []),
        'base_price' => $regular,
        'discount_percent' => $discount,
        'in_stock' => !empty($raw['is_in_stock']) ? 1 : 0,
        'description' => html_to_text((string)($raw['description'] ?? '')),
        'features' => make_features($raw),
        'image' => $imageUrl !== '' ? $imageUrl : null,
        'product_url' => trim((string)($raw['permalink'] ?? '')) ?: null,
    ];
}

/**
 * اجرای مرحله دریافت و ذخیره snapshot.
 */
function run_fetch(): void
{
    try {
        $ids = read_selected_ids(IDS_FILE);
        if (!$ids) {
            throw new RuntimeException('فایل selected_ids.txt هیچ شناسه معتبری ندارد.');
        }

        echo 'تعداد شناسه‌ها: ' . count($ids) . PHP_EOL;

        $products = fetch_products($ids);
        $receivedIds = array_map(
            static fn($product) => (int)($product['id'] ?? 0),
            array_filter($products, 'is_array')
        );
        $missing = array_diff($ids, $receivedIds);

        echo 'تعداد محصولات دریافت‌شده: ' . count($products) . PHP_EOL;
        if ($missing) {
            echo 'شناسه‌های دریافت‌نشده: ' . implode(', ', $missing) . PHP_EOL;
        }

        $json = json_encode(
            $products,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        if ($json === false) {
            throw new RuntimeException('خطا در ساخت JSON: ' . json_last_error_msg());
        }

        if (file_put_contents(SNAPSHOT_FILE, $json, LOCK_EX) === false) {
            throw new RuntimeException('ذخیره فایل snapshot ناموفق بود.');
        }

        echo "Snapshot ذخیره شد: database/products_snapshot.json\n";

        if ($products && is_array($products[0])) {
            $sample = clean_product($products[0]);
            echo "\nنمونه نگاشت به ساختار دیتابیس:\n";
            foreach ($sample as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                echo '  ' . $key . ' = ' . ($value === null ? 'NULL' : $value) . PHP_EOL;
            }
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'خطا در دریافت محصولات: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

/**
 * پیدا کردن یا ساخت دسته‌بندی در جدول categories.
 * جدول categories در schema.sql فقط id و name دارد.
 */
function get_or_create_category(PDO $pdo, string $name): int
{
    $find = $pdo->prepare('SELECT id FROM categories WHERE name = ? ORDER BY id ASC LIMIT 1');
    $find->execute([$name]);
    $id = $find->fetchColumn();

    if ($id !== false) {
        return (int)$id;
    }

    $insert = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
    $insert->execute([$name]);

    return (int)$pdo->lastInsertId();
}

/**
 * وارد کردن snapshot به دو جدول categories و products مطابق schema.sql.
 * هیچ جدولی حذف یا پاک‌سازی نمی‌شود.
 */
function run_import(): void
{
    if (!is_file(SNAPSHOT_FILE)) {
        fwrite(STDERR, "خطا: فایل snapshot پیدا نشد. ابتدا --fetch را اجرا کنید.\n");
        exit(1);
    }

    $items = json_decode((string)file_get_contents(SNAPSHOT_FILE), true);
    if (!is_array($items)) {
        fwrite(STDERR, "خطا: محتوای snapshot معتبر نیست.\n");
        exit(1);
    }

    require_once __DIR__ . '/../app/bootstrap.php';

    try {
        $pdo = \App\Support\Db::pdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $productStmt = $pdo->prepare(
            'INSERT INTO products
                (woo_id, name, brand, category_id, base_price, discount_percent,
                 in_stock, description, features, image, product_url)
             VALUES
                (:woo_id, :name, :brand, :category_id, :base_price, :discount_percent,
                 :in_stock, :description, :features, :image, :product_url)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                brand = VALUES(brand),
                category_id = VALUES(category_id),
                base_price = VALUES(base_price),
                discount_percent = VALUES(discount_percent),
                in_stock = VALUES(in_stock),
                description = VALUES(description),
                features = VALUES(features),
                image = VALUES(image),
                product_url = VALUES(product_url)'
        );

        $success = 0;
        $rejected = [];
        $pdo->beginTransaction();

        try {
            foreach ($items as $index => $raw) {
                $rowNumber = $index + 1;

                if (!is_array($raw)) {
                    $rejected[] = "{$rowNumber} (ساختار محصول نامعتبر)";
                    continue;
                }

                $product = clean_product($raw);
                $errors = [];

                if ($product['woo_id'] <= 0) {
                    $errors[] = 'شناسه نامعتبر';
                }
                if ($product['name'] === '') {
                    $errors[] = 'نام محصول خالی است';
                }
                if ($product['base_price'] === null || $product['base_price'] <= 0) {
                    $errors[] = 'قیمت پایه نامعتبر است';
                }

                if ($errors) {
                    $rejected[] = "{$rowNumber} (" . implode('، ', $errors) . ')';
                    continue;
                }

                $categoryId = get_or_create_category($pdo, $product['category']);

                $productStmt->execute([
                    ':woo_id' => $product['woo_id'],
                    ':name' => $product['name'],
                    ':brand' => mb_substr($product['brand'], 0, 100),
                    ':category_id' => $categoryId,
                    ':base_price' => $product['base_price'],
                    ':discount_percent' => $product['discount_percent'],
                    ':in_stock' => $product['in_stock'],
                    ':description' => $product['description'] !== '' ? $product['description'] : null,
                    ':features' => $product['features'],
                    ':image' => $product['image'],
                    ':product_url' => $product['product_url'],
                ]);

                $success++;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        echo "واردسازی تمام شد.\n";
        echo "موفق: {$success}\n";
        echo 'ردشده: ' . count($rejected) . "\n";

        if ($rejected) {
            echo "جزئیات محصولات ردشده:\n";
            foreach ($rejected as $item) {
                echo '- ' . $item . PHP_EOL;
            }
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'خطای واردسازی: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

/**
 * ورودی CLI:
 * php scripts/import_products.php --fetch  دریافت API و ساخت snapshot
 * php scripts/import_products.php          واردسازی snapshot در دیتابیس
 */
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (in_array('--fetch', $argv, true)) {
        run_fetch();
    } else {
        run_import();
    }
}
