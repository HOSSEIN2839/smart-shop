<?php
declare(strict_types=1);

namespace App\Models;

/**
 * انتخاب قیمت نهایی محصول بر اساس قیمت‌های ذخیره‌شده در دیتابیس.
 * current_price قیمت نهایی معتبر منبع است و بر محاسبه مجدد اولویت دارد.
 */
final class PriceCalculator
{
    public static function finalPrice(
        ?int $currentPrice,
        ?int $salePrice,
        int $regularPrice,
        bool $onSale
    ): int {
        if ($currentPrice !== null) {
            return max(0, $currentPrice);
        }

        if ($onSale && $salePrice !== null) {
            return max(0, $salePrice);
        }

        return max(0, $regularPrice);
    }

    public static function discountPercent(
        int $regularPrice,
        ?int $salePrice,
        bool $onSale
    ): int {
        $base = max(0, $regularPrice);

        if (
            !$onSale ||
            $base <= 0 ||
            $salePrice === null ||
            $salePrice <= 0 ||
            $salePrice >= $base
        ) {
            return 0;
        }

        $percent = (int) round(
            (($base - $salePrice) / $base) * 100
        );

        return max(0, min(90, $percent));
    }
}
