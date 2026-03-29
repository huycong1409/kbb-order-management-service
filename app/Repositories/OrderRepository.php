<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
use stdClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $sortable = ['order_date', 'profit', 'total_selling'];
        $sortCol  = in_array($filters['sort'] ?? '', $sortable) ? $filters['sort'] : 'order_date';
        $sortDir  = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query = Order::with(['shop', 'items.productVariant', 'items.product', 'items.order']);

        if ($sortCol === 'profit') {
            $query->orderByRaw('
                (SELECT COALESCE(SUM(oi.selling_price),0) FROM order_items oi WHERE oi.order_id = orders.id)
                - (orders.fixed_fee + orders.service_fee + orders.payment_fee + orders.pi_ship)
                - (SELECT COALESCE(SUM(oi2.selling_price * 0.015),0) FROM order_items oi2 WHERE oi2.order_id = orders.id)
                - (SELECT COALESCE(SUM(oi3.quantity * oi3.cost_price),0) FROM order_items oi3 WHERE oi3.order_id = orders.id)
                ' . $sortDir
            );
        } elseif ($sortCol === 'total_selling') {
            $query->orderByRaw('
                (SELECT COALESCE(SUM(oi.selling_price),0) FROM order_items oi WHERE oi.order_id = orders.id)
                ' . $sortDir
            );
        } else {
            $query->orderBy('order_date', $sortDir);
        }

        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_code', 'like', "%{$filters['search']}%")
                  ->orWhereHas('items', function ($q2) use ($filters) {
                      $q2->where('product_name', 'like', "%{$filters['search']}%")
                         ->orWhere('product_sku', 'like', "%{$filters['search']}%");
                  });
            });
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (!empty($filters['product_name'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_name', 'like', "%{$filters['product_name']}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['loss_only'])) {
            // profit = SUM(selling) - shared_fees - SUM(tax) - SUM(cost)
            $query->whereRaw('
                (SELECT COALESCE(SUM(oi.selling_price),0) FROM order_items oi WHERE oi.order_id = orders.id)
                - (orders.fixed_fee + orders.service_fee + orders.payment_fee + orders.pi_ship)
                - (SELECT COALESCE(SUM(oi2.selling_price * 0.015),0) FROM order_items oi2 WHERE oi2.order_id = orders.id)
                - (SELECT COALESCE(SUM(oi3.quantity * oi3.cost_price),0) FROM order_items oi3 WHERE oi3.order_id = orders.id)
                < 0
            ');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): Order
    {
        return Order::with(['shop', 'items.product', 'items.productVariant', 'items.order'])->findOrFail($id);
    }

    public function findByCode(int $shopId, string $orderCode): ?Order
    {
        return Order::where('shop_id', $shopId)
            ->where('order_code', $orderCode)
            ->first();
    }

    public function createOrUpdate(array $orderData, array $items): Order
    {
        return DB::transaction(function () use ($orderData, $items) {
            $order = Order::updateOrCreate(
                ['shop_id' => $orderData['shop_id'], 'order_code' => $orderData['order_code']],
                $orderData
            );

            // Xoá items cũ và tạo lại để đảm bảo đồng bộ với file import mới
            $order->items()->delete();

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order->load('items');
        });
    }

    /**
     * Xoá tất cả đơn hàng của shop trong 1 tháng cụ thể.
     * Dùng trước khi import để ghi đè dữ liệu tháng đó.
     * Trả về số đơn đã xoá.
     */
    public function deleteForShopInMonth(int $shopId, int $year, int $month): int
    {
        $orders = Order::where('shop_id', $shopId)
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->get();

        $count = $orders->count();

        foreach ($orders as $order) {
            $order->items()->delete();
            $order->delete();
        }

        return $count;
    }

    public function getForExport(array $filters = []): Collection
    {
        $query = Order::with(['shop', 'items.productVariant', 'items.product', 'items.order']);

        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_code', 'like', "%{$filters['search']}%")
                  ->orWhereHas('items', fn ($q2) => $q2->where('product_name', 'like', "%{$filters['search']}%"));
            });
        }
        if (!empty($filters['product_name'])) {
            $query->whereHas('items', fn ($q) => $q->where('product_name', 'like', "%{$filters['product_name']}%"));
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['loss_only'])) {
            $query->whereRaw('
                (SELECT COALESCE(SUM(oi.selling_price),0) FROM order_items oi WHERE oi.order_id = orders.id)
                - (orders.fixed_fee + orders.service_fee + orders.payment_fee + orders.pi_ship)
                - (SELECT COALESCE(SUM(oi2.selling_price * 0.015),0) FROM order_items oi2 WHERE oi2.order_id = orders.id)
                - (SELECT COALESCE(SUM(oi3.quantity * oi3.cost_price),0) FROM order_items oi3 WHERE oi3.order_id = orders.id)
                < 0
            ');
        }

        return $query->orderBy('order_date', 'desc')->get();
    }

    /**
     * Tính tổng lợi nhuận của 1 shop trong 1 ngày cụ thể.
     * Lợi nhuận = Tổng giá bán - (Phí cố định đơn + Thuế + Tổng vốn)
     */
    public function getProfitByDate(int $shopId, string $date): float
    {
        $orders = Order::with(['items.productVariant', 'items.product', 'items.order'])
            ->forShop($shopId)
            ->whereDate('order_date', $date)
            ->get();

        return $orders->sum(fn ($order) => $order->profit);
    }

    /**
     * Tính tổng lợi nhuận của 1 shop trong 1 tháng.
     */
    public function getProfitByMonth(int $shopId, int $year, int $month): float
    {
        $orders = Order::with(['items.productVariant', 'items.product', 'items.order'])
            ->forShop($shopId)
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->get();

        return $orders->sum(fn ($order) => $order->profit);
    }

    /**
     * Thống kê doanh số / vốn / lợi nhuận nhóm theo sản phẩm + shop.
     * Sử dụng PHP accessor (effective_cost_price) để tính vốn chính xác.
     */
    public function getProductStats(array $filters): Collection
    {
        $query = OrderItem::with(['order', 'product', 'productVariant'])
            ->whereHas('order', function ($q) use ($filters) {
                if (!empty($filters['shop_id'])) {
                    $q->where('shop_id', $filters['shop_id']);
                }
                if (!empty($filters['shop_ids'])) {
                    $q->whereIn('shop_id', $filters['shop_ids']);
                }
                if (!empty($filters['date_from'])) {
                    $q->whereDate('order_date', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $q->whereDate('order_date', '<=', $filters['date_to']);
                }
            });

        if (!empty($filters['product_ids'])) {
            $query->whereIn('product_id', $filters['product_ids']);
        }

        $items = $query->get();

        return $items
            ->groupBy(function ($item) {
                $pid = $item->product_id ?? 'n_' . md5($item->product_name);
                $sid = $item->order->shop_id;
                return "{$pid}_{$sid}";
            })
            ->map(function ($group) {
                $first = $group->first();
                return (object) [
                    'product_id'    => $first->product_id,
                    'product_name'  => $first->product_name,
                    'shop_id'       => $first->order->shop_id,
                    'total_qty'     => (int) $group->sum('quantity'),
                    'total_revenue' => (float) $group->sum('selling_price'),
                    'total_cost'    => (float) $group->sum('total_cost'),
                    'total_profit'  => (float) $group->sum('item_profit_before_shared_fees'),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();
    }

    /**
     * Trả về tóm tắt tháng: số đơn, doanh thu, lợi nhuận trước ADS.
     * Dùng SQL thay vì load toàn bộ orders vào PHP.
     */
    public function getMonthSummaryStats(int $shopId, int $year, int $month): array
    {
        $itemSub = DB::table('order_items')
            ->select(
                'order_id',
                DB::raw('SUM(selling_price)         AS selling_total'),
                DB::raw('SUM(selling_price * 0.015) AS tax_total'),
                DB::raw('SUM(quantity * cost_price)  AS cost_total')
            )
            ->groupBy('order_id');

        $row = DB::table('orders AS o')
            ->joinSub($itemSub, 'oi', 'oi.order_id', '=', 'o.id')
            ->select(
                DB::raw('COUNT(DISTINCT o.id)                                                   AS order_count'),
                DB::raw('SUM(oi.selling_total)                                                  AS total_selling'),
                DB::raw('SUM(oi.cost_total + oi.tax_total
                             + o.fixed_fee + o.service_fee + o.payment_fee + o.pi_ship)        AS total_cost'),
                DB::raw('SUM(oi.selling_total
                             - (o.fixed_fee + o.service_fee + o.payment_fee + o.pi_ship)
                             - oi.tax_total - oi.cost_total)                                    AS profit')
            )
            ->where('o.shop_id', $shopId)
            ->whereYear('o.order_date', $year)
            ->whereMonth('o.order_date', $month)
            ->first();

        return [
            'order_count'   => (int)   ($row->order_count   ?? 0),
            'total_selling' => (float) ($row->total_selling  ?? 0),
            'total_cost'    => (float) ($row->total_cost     ?? 0),
            'profit'        => (float) ($row->profit         ?? 0),
        ];
    }

    /**
     * Lợi nhuận theo từng ngày trong tháng — 1 SQL query thay vì gọi getProfitByDate() 28-31 lần.
     * Trả về: ['2026-03-01' => 1234567.0, ...]
     */
    public function getProfitByDateGrouped(int $shopId, int $year, int $month): array
    {
        $itemSub = DB::table('order_items')
            ->select(
                'order_id',
                DB::raw('SUM(selling_price)         AS selling_total'),
                DB::raw('SUM(selling_price * 0.015) AS tax_total'),
                DB::raw('SUM(quantity * cost_price)  AS cost_total')
            )
            ->groupBy('order_id');

        $rows = DB::table('orders AS o')
            ->joinSub($itemSub, 'oi', 'oi.order_id', '=', 'o.id')
            ->select(
                DB::raw('DATE(o.order_date) AS order_day'),
                DB::raw('SUM(
                    oi.selling_total
                    - (o.fixed_fee + o.service_fee + o.payment_fee + o.pi_ship)
                    - oi.tax_total - oi.cost_total
                ) AS day_profit')
            )
            ->where('o.shop_id', $shopId)
            ->whereYear('o.order_date', $year)
            ->whereMonth('o.order_date', $month)
            ->groupBy(DB::raw('DATE(o.order_date)'))
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->order_day] = (float) $row->day_profit;
        }

        return $result;
    }

    /**
     * Lợi nhuận (trước ADS) mỗi ngày + shop_ids xuất hiện trong ngày đó.
     * Dùng raw SQL JOIN + GROUP BY — không load toàn bộ orders vào PHP.
     * Trả về: ['2026-03-09' => ['profit' => 1200000, 'shop_ids' => [1, 2]], ...]
     */
    public function getDailyStats(array $filters): array
    {
        // Subquery: gộp selling/tax/cost theo order
        $itemSub = DB::table('order_items')
            ->select(
                'order_id',
                DB::raw('SUM(selling_price)         AS selling_total'),
                DB::raw('SUM(selling_price * 0.015) AS tax_total'),
                DB::raw('SUM(quantity * cost_price)  AS cost_total')
            )
            ->groupBy('order_id');

        $query = DB::table('orders AS o')
            ->joinSub($itemSub, 'oi', 'oi.order_id', '=', 'o.id')
            ->select(
                DB::raw('DATE(o.order_date) AS order_day'),
                DB::raw('GROUP_CONCAT(DISTINCT o.shop_id ORDER BY o.shop_id) AS shop_ids_str'),
                DB::raw('SUM(
                    oi.selling_total
                    - (o.fixed_fee + o.service_fee + o.payment_fee + o.pi_ship)
                    - oi.tax_total
                    - oi.cost_total
                ) AS day_profit')
            )
            ->groupBy(DB::raw('DATE(o.order_date)'));

        if (!empty($filters['shop_id'])) {
            $query->where('o.shop_id', $filters['shop_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('o.order_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('o.order_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('o.order_code', 'like', "%{$s}%")
                  ->orWhereExists(fn ($q2) => $q2->from('order_items')
                      ->whereColumn('order_items.order_id', 'o.id')
                      ->where('order_items.product_name', 'like', "%{$s}%"));
            });
        }

        if (!empty($filters['product_name'])) {
            $pn = $filters['product_name'];
            $query->whereExists(fn ($q) => $q->from('order_items')
                ->whereColumn('order_items.order_id', 'o.id')
                ->where('order_items.product_name', 'like', "%{$pn}%"));
        }

        if (!empty($filters['loss_only'])) {
            $query->havingRaw('day_profit < 0');
        }

        $result = [];
        foreach ($query->get() as $row) {
            $shopIds = array_map('intval', explode(',', (string) $row->shop_ids_str));
            $result[$row->order_day] = [
                'profit'   => (float) $row->day_profit,
                'shop_ids' => $shopIds,
            ];
        }

        return $result;
    }
}
