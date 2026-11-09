<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function settings(): StoreSetting
    {
        return StoreSetting::current();
    }

    public function categories()
    {
        return Category::orderBy('sort')->get();
    }

    public function products(Request $request)
    {
        $q = Product::with('productVariants')->where('is_active', true)->orderByDesc('created_at');

        if (in_array($request->query('gender'), ['men', 'women'], true)) {
            $q->where('gender', $request->query('gender'));
        }
        if (filter_var($request->query('featured'), FILTER_VALIDATE_BOOLEAN)) {
            $q->where('is_featured', true);
        }
        $limit = (int) $request->query('limit');
        if ($limit > 0) {
            $q->limit(min($limit, 100));
        }

        return $q->get();
    }

    public function product(string $slug): JsonResponse
    {
        $product = Product::with('productVariants')->where('slug', $slug)->where('is_active', true)->first();

        return $product ? response()->json($product) : response()->json(['message' => 'Not found'], 404);
    }

    // Product ids most often ordered together with this one (cancelled orders ignored).
    public function boughtTogether(string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([]);
        }

        $ids = DB::table('order_items as mine')
            ->join('order_items as other', function ($join) {
                $join->on('other.order_id', '=', 'mine.order_id')
                    ->on('other.product_id', '<>', 'mine.product_id');
            })
            ->join('orders as o', 'o.id', '=', 'mine.order_id')
            ->join('products as p', 'p.id', '=', 'other.product_id')
            ->where('mine.product_id', $id)
            ->where('o.status', '<>', 'cancelled')
            ->where('p.is_active', true)
            ->groupBy('other.product_id')
            ->orderByRaw('COUNT(DISTINCT other.order_id) DESC')
            ->limit(8)
            ->pluck('other.product_id');

        return response()->json($ids->map(fn ($pid) => ['product_id' => $pid])->values());
    }
}
