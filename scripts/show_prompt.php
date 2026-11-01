<?php
declare(strict_types=1);
// اجرا: php scripts/show_prompt.php "یک ساعت مردانه تا ۶ میلیون می‌خواهم"
// پیام نهایی را که PromptGenerator برای هوش مصنوعی می‌سازد چاپ می‌کند (بدون تماس با هوش مصنوعی و بدون دیتابیس).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/bootstrap.php';

use App\Models\ProductRepository;
use App\Prompt\PromptGenerator;
use App\Tools\ToolRegistry;

$text = $argv[1] ?? 'درود، یک ساعت مردانه تا ۶ میلیون می‌خواهم';
$gen = new PromptGenerator(new ToolRegistry(new ProductRepository()));
$history = [
    ['role' => 'user', 'content' => 'سلام'],
    ['role' => 'assistant', 'content' => 'درود! بودجه‌تان چقدر است؟'],
];
$built = $gen->build($history, $text);

foreach ($built['messages'] as $i => $m) {
    echo "--- پیام " . ($i + 1) . " | نقش: " . $m['role'] . " ---\n";
    echo $m['content'] . "\n\n";
}
echo "--- ابزارهای مجاز: ";
echo implode('، ', array_map(static fn(array $t): string => $t['function']['name'], $built['tools'])) . " ---\n";
