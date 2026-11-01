<?php
// تنظیمات اتصال به هوش مصنوعی (محرمانه نیست؛ کلید در secrets.php است)
// فرمت درخواست: «Chat Completions» سازگار با OpenAI (با Function Calling).
// اگر سرویس انتخابی شما این فرمت را دارد فقط base_url و model را عوض کنید.
return [
    'mode'            => 'mock',   // mock = بدون تماس واقعی (برای تست) | live = تماس واقعی با هوش مصنوعی
    'base_url'        => 'https://api.openai.com',     // آدرس پایه سرویس (بدون / آخر)
    'chat_path'       => '/v1/chat/completions',       // مسیر چت در سرویس
    'model'           => 'gpt-4o-mini',                // نام مدل را از مستندات سرویس بگذارید
    'temperature'     => 0.3,
    'max_output_tokens' => 400,
    'connect_timeout' => 8,        // ثانیه
    'timeout'         => 25,       // ثانیه
    'retries'         => 1,        // یک بار تلاش دوباره برای خطای شبکه یا ۵xx
];
