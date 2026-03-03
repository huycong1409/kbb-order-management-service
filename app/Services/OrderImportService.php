<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OrderImportService
{
    // Mapping cột từ file Shopee export (0-indexed)
    private const COL_ORDER_CODE     = 0;  // Mã đơn hàng
    private const COL_PACKAGE_CODE   = 1;  // Mã Kiện Hàng
    private const COL_ORDER_DATE     = 2;  // Ngày đặt hàng
    private const COL_STATUS         = 3;  // Trạng thái đơn hàng
    private const COL_TRACKING       = 7;  // Mã vận đơn
    private const COL_CARRIER        = 8;  // Đơn vị vận chuyển
    private const COL_PRODUCT_SKU    = 15; // SKU sản phẩm
    private const COL_PRODUCT_NAME   = 16; // Tên sản phẩm
    private const COL_VARIANT_SKU    = 19; // SKU phân loại hàng
    private const COL_VARIANT_NAME   = 20; // Tên phân loại hàng
    private const COL_ORIGINAL_PRICE = 21; // Giá gốc
    private const COL_SALE_PRICE     = 25; // Giá ưu đãi
    private const COL_QUANTITY       = 26; // Số lượng
    private const COL_SELLING_PRICE  = 28; // Tổng giá bán (sản phẩm)
    private const COL_CANCEL_REASON  = 5;  // Cột F: Lý do hủy — có giá trị = đơn hủy → skip
    private const COL_RETURN_STATUS  = 14; // Cột O: Trạng thái hoàn tiền
    private const COL_PI_SHIP        = 40; // Phí vận chuyển (dự kiến) — không dùng nữa
    private const COL_PAYMENT_METHOD = 48; // Phương thức thanh toán

    // Pi ship cố định 1620đ/đơn (chỉ áp dụng cho đơn hoàn thành & đơn bị hoàn được chấp thuận)
    private const PI_SHIP_FIXED = 1620.0;

    // Giá trị cột O để xác định đơn bị hoàn được chấp thuận
    private const RETURN_ACCEPTED = 'Đã Chấp Thuận Yêu Cầu';

    // Sản phẩm quà tặng — không tính toán, không hiển thị, bỏ qua khi import
    private const GIFT_PRODUCT_NAMES = [
        'Túi Dây chun sắc màu đủ màu sắc , Thun cột tóc , Vòng nịt buộc tóc',
    ];

    /**
     * Các cột quan trọng cần kiểm tra header.
     * Key = index cột (0-based), Value = chuỗi phải CHỨA trong tên cột (không phân biệt hoa/thường).
     * Chỉ check các cột ảnh hưởng trực tiếp đến tính toán dữ liệu.
     */
    private const EXPECTED_HEADERS = [
        self::COL_ORDER_CODE     => 'mã đơn hàng',
        self::COL_STATUS         => 'trạng thái đơn hàng',
        self::COL_CANCEL_REASON  => 'lý do',           // cột F: lý do hủy
        self::COL_RETURN_STATUS  => 'trả hàng',        // cột O: trạng thái hoàn
        self::COL_PRODUCT_NAME   => 'tên sản phẩm',
        self::COL_QUANTITY       => 'số lượng',
        self::COL_SELLING_PRICE  => 'tổng giá bán',
        self::COL_FIXED_FEE      => 'cố định',
        self::COL_SERVICE_FEE    => 'dịch vụ',
        self::COL_PAYMENT_FEE    => 'thanh toán',
    ];

    private const COL_FIXED_FEE      = 49; // Phí cố định
    private const COL_SERVICE_FEE    = 50; // Phí Dịch Vụ
    private const COL_PAYMENT_FEE    = 51; // Phí thanh toán
    private const COL_BUYER          = 53; // Người mua
    private const COL_RECIPIENT      = 54; // Tên người nhận
    private const COL_PHONE          = 55; // Số điện thoại
    private const COL_PROVINCE       = 56; // Tỉnh/Thành phố
    private const COL_ADDRESS        = 59; // Địa chỉ nhận hàng
    private const COL_NOTE           = 61; // Ghi chú

    public function __construct(
        private readonly OrderRepositoryInterface   $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    /**
     * Import file Excel Shopee cho 1 shop cụ thể.
     * Trả về thống kê: số đơn imported/skipped/errors.
     */
    public function import(UploadedFile $file, int $shopId): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getSheetByName('orders') ?? $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false); // 0-indexed rows

        // Kiểm tra header row trước khi bỏ đi
        $columnWarnings = $this->validateHeaders($rows[0] ?? []);

        // Bỏ header row
        array_shift($rows);

        // Group theo order_code (lấy tất cả, sẽ phân loại theo cột F & O bên dưới)
        $grouped = collect($rows)->groupBy(fn ($row) => trim((string) ($row[self::COL_ORDER_CODE] ?? '')));

        // Detect tháng/năm từ file → xoá đơn cũ của tháng đó trước khi import
        [$year, $month] = $this->detectMonthFromRows($rows);
        $deleted = 0;
        if ($year && $month) {
            $deleted = $this->orderRepository->deleteForShopInMonth($shopId, $year, $month);
        }

        // Cache product lookup để tránh query N+1
        $productCache = $this->buildProductCache($shopId);

        $stats = ['imported' => 0, 'skipped' => 0, 'deleted' => $deleted, 'errors' => [], 'column_warnings' => $columnWarnings];

        foreach ($grouped as $orderCode => $orderRows) {
            if (empty($orderCode)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $firstRow     = $orderRows->first();
                $cancelReason = trim((string) ($firstRow[self::COL_CANCEL_REASON] ?? ''));
                $returnStatus = trim((string) ($firstRow[self::COL_RETURN_STATUS] ?? ''));

                if ($cancelReason !== '') {
                    // Cột F có giá trị = đơn hủy → bỏ qua, không ghi nhận
                    $stats['skipped']++;
                    continue;
                }

                if ($returnStatus === self::RETURN_ACCEPTED) {
                    // Cột O = "Đã Chấp Thuận Yêu Cầu" = đơn bị hoàn được chấp thuận
                    // → pi_ship = 1620, tất cả phí/doanh thu = 0, không có items
                    $orderData = $this->buildReturnedOrderData($shopId, $orderCode, $firstRow);
                    $this->orderRepository->createOrUpdate($orderData, []);
                } else {
                    // Đơn thường (hoàn thành): import đầy đủ, pi_ship cố định 1620
                    $orderData = $this->buildOrderData($shopId, $orderCode, $firstRow);
                    $items     = $this->buildOrderItems($orderRows, $productCache);
                    $this->orderRepository->createOrUpdate($orderData, $items);
                }

                $stats['imported']++;
            } catch (\Throwable $e) {
                $stats['errors'][] = "Order {$orderCode}: " . $e->getMessage();
            }
        }

        return $stats;
    }

    /**
     * Kiểm tra header row của file Excel có khớp với cấu trúc Shopee dự kiến không.
     * Trả về danh sách cảnh báo nếu phát hiện cột bị đổi tên hoặc sai vị trí.
     */
    private function validateHeaders(array $headerRow): array
    {
        if (empty($headerRow)) {
            return ['Không đọc được header row — có thể file không đúng định dạng.'];
        }

        $warnings = [];

        foreach (self::EXPECTED_HEADERS as $colIndex => $expectedKeyword) {
            $actual = mb_strtolower(trim((string) ($headerRow[$colIndex] ?? '')));

            if ($actual === '') {
                $warnings[] = "Cột " . ($colIndex + 1) . " (vị trí " . chr(65 + $colIndex) . "): trống — dự kiến chứa \"{$expectedKeyword}\".";
            } elseif (!str_contains($actual, $expectedKeyword)) {
                $warnings[] = "Cột " . ($colIndex + 1) . " (" . chr(65 + $colIndex) . "): tìm thấy \"{$headerRow[$colIndex]}\" — dự kiến chứa \"{$expectedKeyword}\".";
            }
        }

        return $warnings;
    }

    /**
     * Detect tháng/năm từ danh sách rows — lấy từ ngày đầu tiên tìm được.
     * File Shopee luôn trong 1 tháng nên lấy bất kỳ ngày nào cũng đúng.
     * Trả về [year, month] hoặc [null, null] nếu không đọc được.
     */
    private function detectMonthFromRows(array $rows): array
    {
        foreach ($rows as $row) {
            $raw = $row[self::COL_ORDER_DATE] ?? null;
            if (empty($raw)) continue;

            try {
                $date = is_numeric($raw)
                    ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($raw)
                    : new \DateTime((string) $raw);

                return [(int) $date->format('Y'), (int) $date->format('n')];
            } catch (\Throwable) {
                continue;
            }
        }

        return [null, null];
    }

    private function buildOrderData(int $shopId, string $orderCode, array $row): array
    {
        return [
            'shop_id'        => $shopId,
            'order_code'     => $orderCode,
            'package_code'   => trim((string) ($row[self::COL_PACKAGE_CODE] ?? '')),
            'order_date'     => $this->parseDate($row[self::COL_ORDER_DATE] ?? null),
            'status'         => 'completed',
            'fixed_fee'      => $this->parseAmount($row[self::COL_FIXED_FEE] ?? 0),
            'service_fee'    => $this->parseAmount($row[self::COL_SERVICE_FEE] ?? 0),
            'payment_fee'    => $this->parseAmount($row[self::COL_PAYMENT_FEE] ?? 0),
            'pi_ship'        => self::PI_SHIP_FIXED, // Cố định 1620đ, không lấy từ Excel
            'tracking_number'  => trim((string) ($row[self::COL_TRACKING] ?? '')),
            'shipping_carrier' => trim((string) ($row[self::COL_CARRIER] ?? '')),
            'payment_method'   => trim((string) ($row[self::COL_PAYMENT_METHOD] ?? '')),
            'buyer_username'   => trim((string) ($row[self::COL_BUYER] ?? '')),
            'recipient_name'   => trim((string) ($row[self::COL_RECIPIENT] ?? '')),
            'phone'            => trim((string) ($row[self::COL_PHONE] ?? '')),
            'province'         => trim((string) ($row[self::COL_PROVINCE] ?? '')),
            'address'          => trim((string) ($row[self::COL_ADDRESS] ?? '')),
            'note'             => trim((string) ($row[self::COL_NOTE] ?? '')),
        ];
    }

    /**
     * Đơn bị hoàn được chấp thuận (cột O = "Đã Chấp Thuận Yêu Cầu"):
     * chỉ ghi pi_ship = 1620, tất cả phí/doanh thu = 0, không có items.
     */
    private function buildReturnedOrderData(int $shopId, string $orderCode, array $row): array
    {
        return [
            'shop_id'          => $shopId,
            'order_code'       => $orderCode,
            'package_code'     => trim((string) ($row[self::COL_PACKAGE_CODE] ?? '')),
            'order_date'       => $this->parseDate($row[self::COL_ORDER_DATE] ?? null),
            'status'           => 'returned',
            'fixed_fee'        => 0,
            'service_fee'      => 0,
            'payment_fee'      => 0,
            'pi_ship'          => self::PI_SHIP_FIXED, // Vẫn trừ 1620đ
            'tracking_number'  => trim((string) ($row[self::COL_TRACKING] ?? '')),
            'shipping_carrier' => trim((string) ($row[self::COL_CARRIER] ?? '')),
            'payment_method'   => trim((string) ($row[self::COL_PAYMENT_METHOD] ?? '')),
            'buyer_username'   => trim((string) ($row[self::COL_BUYER] ?? '')),
            'recipient_name'   => trim((string) ($row[self::COL_RECIPIENT] ?? '')),
            'phone'            => trim((string) ($row[self::COL_PHONE] ?? '')),
            'province'         => trim((string) ($row[self::COL_PROVINCE] ?? '')),
            'address'          => trim((string) ($row[self::COL_ADDRESS] ?? '')),
            'note'             => trim((string) ($row[self::COL_NOTE] ?? '')),
        ];
    }

    private function isGiftProduct(string $productName): bool
    {
        foreach (self::GIFT_PRODUCT_NAMES as $giftName) {
            if (mb_strtolower(trim($productName)) === mb_strtolower(trim($giftName))) {
                return true;
            }
        }
        return false;
    }

    private function buildOrderItems(Collection $rows, array $productCache): array
    {
        return $rows->filter(function ($row) {
            // Bỏ qua sản phẩm quà tặng
            $productName = trim((string) ($row[self::COL_PRODUCT_NAME] ?? ''));
            return !$this->isGiftProduct($productName);
        })->map(function ($row) use ($productCache) {
            $productName = trim((string) ($row[self::COL_PRODUCT_NAME] ?? ''));
            $variantName = trim((string) ($row[self::COL_VARIANT_NAME] ?? ''));

            // Tra cứu cost_price từ product/variant đã được quản trị
            [$productId, $variantId, $costPrice] = $this->lookupProductCost(
                $productName, $variantName, $productCache
            );

            return [
                'product_id'         => $productId,
                'product_variant_id' => $variantId,
                'product_name'       => $productName,
                'variant_name'       => $variantName ?: null,
                'product_sku'        => trim((string) ($row[self::COL_PRODUCT_SKU] ?? '')) ?: null,
                'variant_sku'        => trim((string) ($row[self::COL_VARIANT_SKU] ?? '')) ?: null,
                'quantity'           => (int) ($row[self::COL_QUANTITY] ?? 1),
                'cost_price'         => $costPrice,
                'original_price'     => $this->parseAmount($row[self::COL_ORIGINAL_PRICE] ?? 0),
                'sale_price'         => $this->parseAmount($row[self::COL_SALE_PRICE] ?? 0),
                'selling_price'      => $this->parseAmount($row[self::COL_SELLING_PRICE] ?? 0),
            ];
        })->values()->toArray();
    }

    /**
     * Build cache: ['product_name' => Product, 'product_name|variant_name' => ProductVariant]
     */
    private function buildProductCache(int $shopId): array
    {
        $products = $this->productRepository->allForShop($shopId);
        $cache    = [];

        foreach ($products as $product) {
            $cache[$product->name] = $product;
            foreach ($product->variants as $variant) {
                $cacheKey            = $product->name . '|' . $variant->name;
                $cache[$cacheKey]    = $variant;
            }
        }

        return $cache;
    }

    private function lookupProductCost(string $productName, string $variantName, array $cache): array
    {
        $variantKey = $productName . '|' . $variantName;

        // Ưu tiên tìm theo product + variant
        if (!empty($variantName) && isset($cache[$variantKey])) {
            $variant = $cache[$variantKey];
            return [$variant->product_id, $variant->id, (float) $variant->cost_price];
        }

        // Fallback: tìm theo product
        if (isset($cache[$productName])) {
            $product = $cache[$productName];
            return [$product->id, null, (float) $product->cost_price];
        }

        return [null, null, 0.0];
    }

    private function parseDate(mixed $value): string
    {
        if (empty($value)) {
            return now()->toDateTimeString();
        }

        // Excel date serial number
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                ->format('Y-m-d H:i:s');
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return now()->toDateTimeString();
        }
    }

    private function parseAmount(mixed $value): float
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }
        // Loại bỏ dấu phân cách ngàn, ký hiệu tiền tệ
        $cleaned = preg_replace('/[^\d,\.-]/', '', (string) $value);
        $cleaned = str_replace(',', '', $cleaned);
        return (float) $cleaned;
    }
}
