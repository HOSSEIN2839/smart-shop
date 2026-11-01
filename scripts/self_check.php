<?php
declare(strict_types=1);
// اجرا: php scripts/self_check.php
// بررسی می‌کند همه‌چیز برای اجرای پروژه آماده است. هر خط ✔ یا ✘ نشان می‌دهد.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/bootstrap.php';

use App\Support\Config;
use App\Support\Db;

$fails = 0;
function check(string $label, bool $ok, string $hint = ''): void {
    global $fails;
    echo ($ok ? '✔ ' : '✘ ') . $label . ($ok || $hint === '' ? '' : "\n    ← " . $hint) . "\n";
    if (!$ok) { $fails++; }
}

check('نسخه PHP حداقل 8.0 (الان ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8 نصب کنید.');
foreach (['curl', 'pdo_mysql', 'mbstring', 'json'] as $ext) {
    check("افزونه PHP: $ext", extension_loaded($ext), "افزونه $ext را فعال کنید (روی Ubuntu: apt install php-" . str_replace('pdo_mysql', 'mysql', $ext) . ').');
}

check('فایل config/app.php', is_file(BASE_PATH . '/config/app.php'));
check('فایل config/ai.php', is_file(BASE_PATH . '/config/ai.php'));
check('فایل config/secrets.php', is_file(BASE_PATH . '/config/secrets.php'), 'cp config/secrets.example.php config/secrets.php');
check('فایل config/database.php', is_file(BASE_PATH . '/config/database.php'), 'cp config/database.example.php config/database.php');
check('فایل prompts/system_prompt.fa.txt', is_file(BASE_PATH . '/prompts/system_prompt.fa.txt'));

if (is_file(BASE_PATH . '/config/secrets.php')) {
    $s = Config::get('secrets');
    $keyOk = !empty($s['api_key']) && $s['api_key'] !== 'PUT-API-KEY-HERE';
    check('کلید API در secrets.php نوشته شده', $keyOk, 'کلید را فقط در config/secrets.php بنویسید.');
    check('canary تغییر کرده است', !empty($s['canary']) && $s['canary'] !== 'CHANGE-ME-RANDOM-STRING', 'یک رشته تصادفی بگذارید.');
    check('ip_salt تغییر کرده است', !empty($s['ip_salt']) && $s['ip_salt'] !== 'CHANGE-ME-ANOTHER-RANDOM-STRING', 'یک رشته تصادفی بگذارید.');
}

$prompt = @file_get_contents(BASE_PATH . '/prompts/system_prompt.fa.txt') ?: '';
check('System Prompt شامل {{CANARY}} است', str_contains($prompt, '{{CANARY}}'));

$logDir = BASE_PATH . '/logs';
check('پوشه logs قابل نوشتن است', is_dir($logDir) && is_writable($logDir), 'روی سرور: chown -R www-data:www-data logs');

$ai = Config::get('ai');
echo '  حالت هوش مصنوعی (mode): ' . $ai['mode'] . ' | مدل: ' . $ai['model'] . "\n";

if (is_file(BASE_PATH . '/config/database.php')) {
    try {
        $pdo = Db::pdo();
        check('اتصال به دیتابیس', true);
        foreach (['categories', 'products', 'conversations', 'messages'] as $t) {
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetchColumn() !== false;
            check("جدول $t وجود دارد", $exists, $t === 'conversations' || $t === 'messages'
                ? 'mysql -u root -p smart_shop < database/schema_chat.sql'
                : 'mysql -u root -p smart_shop < database/schema.sql');
        }
        $count = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE in_stock = 1')->fetchColumn();
        check("تعداد محصول موجود: $count", $count > 0, 'برای تست: mysql -u root -p smart_shop < database/seed_sample.sql');
    } catch (Throwable $e) {
        check('اتصال به دیتابیس', false, $e->getMessage());
    }
}

echo $fails === 0 ? "\nهمه‌چیز آماده است ✔\n" : "\n$fails مورد نیاز به رفع دارد ✘\n";
exit($fails === 0 ? 0 : 1);
