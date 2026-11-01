-- ساخت کاربر دیتابیس با کمترین دسترسی (اصل «دستیار فقط می‌خواند»).
-- رمز را عوض کنید و همین رمز را فقط در config/database.php بنویسید. این فایل را بعد از اجرا با رمز واقعی در همگیت نگذارید.
CREATE DATABASE IF NOT EXISTS smart_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- کاربر برنامه (چت): روی محصولات فقط SELECT؛ روی گفتگوها فقط خواندن/نوشتن لازم
CREATE USER IF NOT EXISTS 'shop_user'@'localhost' IDENTIFIED BY 'CHANGE-ME-STRONG-PASSWORD';
GRANT SELECT ON smart_shop.categories TO 'shop_user'@'localhost';
GRANT SELECT ON smart_shop.products TO 'shop_user'@'localhost';
GRANT SELECT, INSERT, UPDATE ON smart_shop.conversations TO 'shop_user'@'localhost';
GRANT SELECT, INSERT ON smart_shop.messages TO 'shop_user'@'localhost';
FLUSH PRIVILEGES;

-- نکته: برای ساخت جدول‌ها و import محصولات (کار B2 و B5) با حساب root وارد MySQL شوید:
--   mysql -u root -p smart_shop < database/schema.sql
--   mysql -u root -p smart_shop < database/schema_chat.sql
-- scripts/test_isolation.php برای پاک کردن گفتگوهای آزمایشی به DELETE نیاز دارد؛ آن را با حساب root اجرا نکنید،
-- فقط موقتاً GRANT DELETE ON smart_shop.conversations بدهید یا از اسکریپت صرف‌نظر کنید.
