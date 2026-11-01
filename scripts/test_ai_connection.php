<?php
declare(strict_types=1);
// اجرا: php scripts/test_ai_connection.php
// فقط اتصال به سرویس هوش مصنوعی را (بدون دیتابیس و بدون وب‌سرور) با کلید واقعی تست می‌کند.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/bootstrap.php';

use App\Ai\AiException;
use App\Ai\OpenAiCompatibleClient;
use App\Support\Config;

$cfg = Config::get('ai');
$key = Config::secret('api_key');
$masked = $key === '' ? '(خالی)' : substr($key, 0, 4) . '***' . substr($key, -2);

echo "آدرس : " . rtrim($cfg['base_url'], '/') . $cfg['chat_path'] . "\n";
echo "مدل   : " . $cfg['model'] . "\n";
echo "کلید  : $masked\n";

$client = new OpenAiCompatibleClient($cfg, $key);   // مستقل از mode؛ همیشه تماس واقعی
$started = microtime(true);
try {
    $resp = $client->chat([
        ['role' => 'system', 'content' => 'فقط فارسی و خیلی کوتاه جواب بده.'],
        ['role' => 'user', 'content' => 'درود'],
    ]);
    $ms = (int) round((microtime(true) - $started) * 1000);
    echo "نتیجه : موفق ✔ ($ms میلی‌ثانیه)\n";
    echo "جواب  : " . trim((string) $resp['content']) . "\n";
    exit(0);
} catch (AiException $e) {
    echo "نتیجه : ناموفق ✘ [" . $e->kind() . "] " . $e->getMessage() . "\n";
    echo "راهنما: auth=کلید اشتباه | rate_limit=اعتبار یا سقف | network=اتصال/فایروال/DNS | server=خطای سرویس | config=تنظیمات\n";
    exit(1);
}
