<?php
declare(strict_types=1);
// اجرا: php scripts/test_isolation.php
// ثابت می‌کند هر کاربر گفتگوی جدا دارد و تاریخچه یک گفتگو در گفتگوی دیگر دیده نمی‌شود. نیاز به دیتابیس دارد.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/bootstrap.php';

use App\Gateway\ConversationStore;

$fails = 0;
function expect(string $label, bool $ok): void {
    global $fails;
    echo ($ok ? '✔ ' : '✘ ') . $label . "\n";
    if (!$ok) { $fails++; }
}

$store = new ConversationStore();
$a = $store->create(hash('sha256', 'test-ip-a'), hash('sha256', 'ua'));
$b = $store->create(hash('sha256', 'test-ip-b'), hash('sha256', 'ua'));

expect('دو گفتگو شناسه‌های متفاوت دارند', $a['public_id'] !== $b['public_id'] && $a['id'] !== $b['id']);
expect('شناسه عمومی ۳۲ حرف هگزادسیمال است', preg_match('/^[a-f0-9]{32}$/', $a['public_id']) === 1);

$store->addMessage($a['id'], 'user', 'پیام محرمانه کاربر A');
$store->addMessage($a['id'], 'assistant', 'جواب برای A');
$store->addMessage($b['id'], 'user', 'پیام کاربر B');

$histA = $store->recentMessages($a['id'], 10);
$histB = $store->recentMessages($b['id'], 10);
expect('گفتگوی A دو پیام دارد', count($histA) === 2);
expect('گفتگوی B یک پیام دارد', count($histB) === 1);
expect('پیام A در تاریخچه B دیده نمی‌شود', !str_contains(json_encode($histB, JSON_UNESCAPED_UNICODE), 'کاربر A'));
expect('شناسه نامعتبر پذیرفته نمی‌شود', $store->findByPublicId('not-a-valid-id') === null);
expect('شمارنده پیام کاربر درست است', ($store->findByPublicId($a['public_id'])['message_count'] ?? -1) === 1);

try {   // پاک‌سازی (اگر کاربر دیتابیس DELETE نداشته باشد، رد می‌شود و مشکلی نیست)
    $store->deleteByPublicId($a['public_id']);
    $store->deleteByPublicId($b['public_id']);
} catch (Throwable $e) {
    echo "  (پاک‌سازی گفتگوهای آزمایشی انجام نشد؛ مشکلی نیست: " . $e->getMessage() . ")\n";
}

echo $fails === 0 ? "\nهمه تست‌ها درست ✔\n" : "\n$fails تست ناموفق ✘\n";
exit($fails === 0 ? 0 : 1);
