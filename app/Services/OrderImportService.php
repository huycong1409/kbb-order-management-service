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
    private const COL_PI_SHIP        = 40; // Phí vận chuyển (dự kiến) → Pi Ship
    private const COL_PAYMENT_METHOD = 48; // Phương thức thanh toán
    private const COL_FIXED_FEE      = 49; // Phí cố định
    private const COL_SERVICE_FEE    = 50; // Phí Dịch Vụ
    private const COL_PAYMENT_FEE    = 51; // Phí thanh toán
    private const COL_BUYER          = 53; // Người mua
    private const COL_RECIPIENT      = 54; // Tên người nhận
    private const COL_PHONE          = 55; // Số điện thoại
    private const COL_PROVINCE       = 56; // Tỉnh/Thành phố
    private const COL_ADDRESS        = 59; // Địa chỉ nhận hàng
    private const COL_NOTE           = 61; // Ghi chú

    // Chỉ import đơn hàng với các trạng thái này
    private const IMPORTABLE_STATUSES = ['Hoàn thành'];

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

        // Bỏ header row
        array_shift($rows);

        // Lọc chỉ lấy đơn Hoàn thành
        $rows = array_filter($rows, function ($row) {
            $status = trim((string) ($row[self::COL_STATUS] ?? ''));
            return in_array($status, self::IMPORTABLE_STATUSES, true);
        });

        // Group theo order_code
        $grouped = collect($rows)->groupBy(fn ($row) => trim((string) ($row[self::COL_ORDER_CODE] ?? '')));

        // Cache product lookup để tránh query N+1
        $productCache = $this->buildProductCache($shopId);

        $stats = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($grouped as $orderCode => $orderRows) {
            if (empty($orderCode)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $firstRow = $orderRows->first();
                $orderData = $this->buildOrderData($shopId, $orderCode, $firstRow);
                $items     = $this->buildOrderItems($orderRows, $productCache);

                $this->orderRepository->createOrUpdate($orderData, $items);
                $stats['imported']++;
            } catch (\Throwable $e) {
                $stats['errors'][] = "Order {$orderCode}: " . $e->getMessage();
            }
        }

        return $stats;
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
            'pi_ship'        => $this->parseAmount($row[self::COL_PI_SHIP] ?? 0),
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

    private function buildOrderItems(Collection $rows, array $productCache): array
    {
        return $rows->map(function ($row) use ($productCache) {
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
