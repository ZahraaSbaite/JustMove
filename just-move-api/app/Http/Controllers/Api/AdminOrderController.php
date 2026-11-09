<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): JsonResponse
    {
        $base = Order::query();

        $status = (string) $request->query('status', 'all');
        if (in_array($status, ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
            $base->where('status', $status);
        }

        $search = trim(str_replace([',', '(', ')', '%'], '', (string) $request->query('search', '')));
        if ($search !== '') {
            $digits = ltrim($search, '#');
            if (ctype_digit($digits) && (int) $digits > 0 && strlen($search) <= 6) {
                $base->where('order_number', (int) $digits);
            } else {
                $like = '%' . str_replace(['\\', '_'], ['\\\\', '\\_'], $search) . '%';
                $base->where(fn ($q) => $q->where('phone', 'like', $like)->orWhere('customer_name', 'like', $like));
            }
        }

        $count = (clone $base)->count();
        $page = max(0, (int) $request->query('page', 0));

        $orders = $base->with('orderItems')
            ->orderByDesc('created_at')
            ->skip($page * self::PER_PAGE)
            ->take(self::PER_PAGE)
            ->get();

        return response()->json(['orders' => $orders, 'count' => $count]);
    }

    // Today's numbers (the day starts at midnight in APP_TIMEZONE).
    public function stats(): JsonResponse
    {
        $rows = Order::where('created_at', '>=', now()->startOfDay())->get(['total', 'status', 'payment_status']);
        $live = $rows->where('status', '!=', 'cancelled');

        return response()->json([
            'today' => $live->count(),
            'revenue' => round((float) $live->sum('total'), 2),
            'pending' => $rows->where('status', 'pending')->count(),
            'checkTransfer' => $rows->where('payment_status', 'awaiting_confirmation')->count(),
        ]);
    }

    public function show(string $id): Order
    {
        return Order::with('orderItems')->findOrFail($id);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])],
            'payment_status' => ['sometimes', Rule::in(['unpaid', 'awaiting_confirmation', 'paid', 'refunded'])],
        ]);

        DB::transaction(function () use ($id, $data) {
            $order = Order::lockForUpdate()->findOrFail($id);

            // Cancelling an order puts its pieces back in stock (once).
            if (($data['status'] ?? null) === 'cancelled' && $order->status !== 'cancelled') {
                OrderItem::where('order_id', $order->id)
                    ->whereNotNull('variant_id')
                    ->selectRaw('variant_id, SUM(quantity) as qty')
                    ->groupBy('variant_id')
                    ->get()
                    ->each(fn ($row) => ProductVariant::whereKey($row->variant_id)->increment('stock', (int) $row->qty));
            }

            $order->update($data);
        });

        return response()->json(['ok' => true]);
    }
}
