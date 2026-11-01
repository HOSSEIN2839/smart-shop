<?php
// نمونه بدون کلید. این فایل را با اسم secrets.php کپی کنید و مقدارها را فقط در همان نسخه بنویسید.
// secrets.php در .gitignore است و هرگز نباید به همگیت برود.
return [
    'api_key' => 'PUT-API-KEY-HERE',
    // یک رشته تصادفی ۱۶ تا ۳۲ حرفی؛ برای تشخیص لو رفتن System Prompt در جواب مدل استفاده می‌شود.
    'canary'  => 'CHANGE-ME-RANDOM-STRING',
    // نمک برای هش کردن IP کاربران (IP خام ذخیره نمی‌شود)
    'ip_salt' => 'CHANGE-ME-ANOTHER-RANDOM-STRING',
];
