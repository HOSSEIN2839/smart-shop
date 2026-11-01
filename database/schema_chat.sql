-- جدول‌های سیستم واسط: هر کاربر یک گفتگوی جدا (conversations) و پیام‌های همان گفتگو (messages).
-- این فایل چیزی را پاک نمی‌کند و چند بار اجرا کردنش بی‌خطر است.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS conversations (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id      CHAR(32) NOT NULL,                 -- شناسه تصادفی گفتگو (فقط در Session سرور نگه داشته می‌شود)
    ip_hash        CHAR(64) NOT NULL,                 -- هش IP (IP خام ذخیره نمی‌شود)
    ua_hash        CHAR(64) NULL,
    status         ENUM('open','closed') NOT NULL DEFAULT 'open',
    message_count  INT UNSIGNED NOT NULL DEFAULT 0,   -- تعداد پیام‌های کاربر
    created_at     DATETIME NOT NULL,
    last_active_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conversations_public (public_id),
    KEY idx_conversations_ip_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,
    role            ENUM('user','assistant') NOT NULL,
    content         TEXT NOT NULL,
    flagged         TINYINT(1) NOT NULL DEFAULT 0,    -- ۱ = پیام مشکوک یا خروجی مسدودشده
    created_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_messages_conv (conversation_id, id),
    KEY idx_messages_created (created_at),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
