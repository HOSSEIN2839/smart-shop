# راه‌اندازی دیتابیس پروژه Smart Shop

این راهنما مراحل ساخت دیتابیس، ایجاد کاربر و اعطای دسترسی‌های لازم در محیط محلی XAMPP را توضیح می‌دهد.

## مرحله ۱: اجرای XAMPP

1. برنامه XAMPP Control Panel را باز کنید.
2. سرویس Apache را با کلیک روی Start اجرا کنید.
3. سرویس MySQL را با کلیک روی Start اجرا کنید.
4. در مرورگر آدرس زیر را باز کنید:

   `http://localhost/phpmyadmin`

## مرحله ۲: ساخت دیتابیس smart_shop

1. در phpMyAdmin روی گزینه New کلیک کنید.
2. در قسمت نام دیتابیس، مقدار `smart_shop` را وارد کنید.
3. در قسمت Collation گزینه `utf8mb4_unicode_ci` را انتخاب کنید.
4. روی Create کلیک کنید.
5. بررسی کنید که دیتابیس `smart_shop` در ستون سمت چپ نمایش داده شود.

## مرحله ۳: ساخت کاربر shop_user

1. در phpMyAdmin وارد تب SQL شوید.
2. دستور زیر را اجرا کنید:

   ```sql
   CREATE USER 'shop_user'@'localhost'
   IDENTIFIED BY 'YOUR_LOCAL_PASSWORD';
   ```

3. مقدار `YOUR_LOCAL_PASSWORD` را با یک رمز مناسب برای محیط محلی جایگزین کنید.
4. اگر کاربر از قبل ساخته شده است، این دستور را دوباره اجرا نکنید.
5. برای بررسی ایجاد کاربر، دستور زیر را اجرا کنید:

   ```sql
   SELECT User, Host
   FROM mysql.user
   WHERE User = 'shop_user';
   ```

6. باید کاربر `shop_user` با میزبان `localhost` در نتیجه نمایش داده شود.

**نکته امنیتی:** رمز واقعی را در مخزن عمومی GitHub قرار ندهید.

## مرحله ۴: اعطای دسترسی به دیتابیس

1. در تب SQL دستور زیر را اجرا کنید:

   ```sql
   GRANT ALL PRIVILEGES ON `smart_shop`.*
   TO 'shop_user'@'localhost';
   ```

2. برای بررسی دسترسی‌ها، دستور زیر را اجرا کنید:

   ```sql
   SHOW GRANTS FOR 'shop_user'@'localhost';
   ```

3. در نتیجه باید دسترسی `ALL PRIVILEGES` روی دیتابیس `smart_shop` نمایش داده شود.

## نتیجه

دیتابیس `smart_shop` ایجاد شده، کاربر `shop_user` با میزبان `localhost` ساخته شده و دسترسی‌های لازم به این دیتابیس اختصاص یافته‌اند.

این تنظیمات برای محیط محلی XAMPP هستند. رمز کاربر و اطلاعات اتصال واقعی نباید در مخزن عمومی منتشر شوند.
