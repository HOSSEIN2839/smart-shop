#!/usr/bin/env bash
# اجرا: bash scripts/smoke_test.sh http://IP-یا-آدرس-سایت
# آزمون سریع از بیرون: ساخت گفتگو ← ارسال پیام ← تست خطاها. نیاز به curl و (برای خواندن JSON) python3 دارد.
BASE="${1:-http://localhost/smart-shop/public}"
BASE="${BASE%/}"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

echo "1) ساخت گفتگوی جدید"
S=$(curl -sS -c "$JAR" -b "$JAR" -X POST "$BASE/api/session.php")
echo "$S"
CSRF=$(echo "$S" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("csrf",""))')
[ -z "$CSRF" ] && { echo "✘ csrf گرفته نشد"; exit 1; }

echo; echo "2) پیام عادی"
curl -sS -c "$JAR" -b "$JAR" -X POST "$BASE/api/chat.php" -H "Content-Type: application/json" -H "X-CSRF-Token: $CSRF" \
  -d '{"message":"درود، یک ساعت مردانه تا ۶ میلیون می‌خواهم"}'

echo; echo "3) پیام خالی ← باید 400"
curl -sS -o /dev/null -w "%{http_code}\n" -c "$JAR" -b "$JAR" -X POST "$BASE/api/chat.php" -H "Content-Type: application/json" -H "X-CSRF-Token: $CSRF" -d '{"message":""}'

echo "4) درخواست GET ← باید 405"
curl -sS -o /dev/null -w "%{http_code}\n" -b "$JAR" "$BASE/api/chat.php"

echo "5) بدون توکن CSRF ← باید 403"
curl -sS -o /dev/null -w "%{http_code}\n" -c "$JAR" -b "$JAR" -X POST "$BASE/api/chat.php" -H "Content-Type: application/json" -d '{"message":"درود"}'

echo "6) بدون کوکی (گفتگوی ناموجود) ← باید 409"
curl -sS -o /dev/null -w "%{http_code}\n" -X POST "$BASE/api/chat.php" -H "Content-Type: application/json" -H "X-CSRF-Token: x" -d '{"message":"درود"}'

echo "7) فایل‌های محرمانه ← همه باید 403 یا 404"
for p in config/secrets.php app/bootstrap.php prompts/system_prompt.fa.txt database/schema.sql .git/config logs/app.log; do
  printf "%s -> " "$p"; curl -sS -o /dev/null -w "%{http_code}\n" "$BASE/$p"
done
