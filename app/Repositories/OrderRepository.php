<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
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

        $query = Order::with(['shop', 'items']);

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
        return Order::with(['shop', 'items.product', 'items.productVariant'])->findOrFail($id);
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

    public function getForExport(array $filters = []): Collection
    {
        $query = Order::with(['shop', 'items']);

        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_code', 'like', "%{$filters['search']}%")
                  ->orWhereHas('items', fn ($q2) => $q2->where('product_name', 'like', "%{$filters['search']}%"));
            });
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
        $orders = Order::with('items')
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
        $orders = Order::with('items')
            ->forShop($shopId)
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->get();

        return $orders->sum(fn ($order) => $order->profit);
    }
}
