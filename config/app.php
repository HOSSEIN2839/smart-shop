<?php
// تنظیمات عمومی برنامه (محرمانه نیست؛ به همگیت می‌رود)
return [
    'env'                 => 'production',      // production | local
    'session_name'        => 'SMARTSHOPSID',
    'max_message_chars'   => 500,               // حداکثر طول پیام کاربر
    'history_messages'    => 10,                // چند پیام آخر گفتگو برای هوش مصنوعی فرستاده شود
    'max_messages_per_conversation' => 40,      // سقف پیام کاربر در هر گفتگو
    'rate_limit_per_minute' => 8,               // حداکثر پیام در دقیقه برای هر گفتگو
    'rate_limit_ip_per_minute' => 20,           // حداکثر پیام در دقیقه برای هر IP
    'max_tool_rounds'     => 4,                 // حداکثر دور Function Calling
    'shop_name'           => 'دستیار خرید هوشمند',
    'log_file'            => __DIR__ . '/../logs/app.log',
    'prompt_file'         => __DIR__ . '/../prompts/system_prompt.fa.txt',
];
