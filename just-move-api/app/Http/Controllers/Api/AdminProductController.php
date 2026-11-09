<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index()
    {
        return Product::with('productVariants')->orderByDesc('created_at')->get();
    }

    public function show(string $id): Product
    {
        return Product::with('productVariants')->findOrFail($id);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return $this->save($request, Product::findOrFail($id));
    }

    public function destroy(string $id): JsonResponse
    {
        Product::findOrFail($id)->delete(); // variants are deleted with it; past orders keep their snapshot
        return response()->json(['ok' => true]);
    }

    private function save(Request $request, ?Product $product): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'gender' => ['required', Rule::in(['men', 'women'])],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:2048'],
            'styles' => ['nullable', 'array'],
            'styles.*' => ['string', 'max:50'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'product_variants' => ['nullable', 'array'],
            'product_variants.*.id' => ['nullable', 'uuid'],
            'product_variants.*.size' => ['required', 'string', 'max:40'],
            'product_variants.*.color' => ['nullable', 'string', 'max:60'],
            'product_variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        $variants = array_values($data['product_variants'] ?? []);

        $seen = [];
        foreach ($variants as $v) {
            $key = mb_strtolower(trim($v['size'])) . '|' . mb_strtolower(trim($v['color'] ?? ''));
            if (isset($seen[$key])) {
                abort(422, 'Two sizes have the same size and colour');
            }
            $seen[$key] = true;
        }

        $saved = DB::transaction(function () use ($product, $data, $variants) {
            $fields = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'gender' => $data['gender'],
                'category_id' => $data['category_id'] ?? null,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'images' => array_values($data['images'] ?? []),
                'styles' => array_values($data['styles'] ?? []),
                'is_active' => $data['is_active'] ?? true,
                'is_featured' => $data['is_featured'] ?? false,
            ];

            $product = $product ? tap($product)->update($fields) : Product::create($fields);

            // Remove variants that were deleted in the form, then update/insert the rest.
            $keep = collect($variants)->pluck('id')->filter()->values()->all();
            ProductVariant::where('product_id', $product->id)->whereNotIn('id', $keep)->delete();

            foreach ($variants as $i => $v) {
                $attrs = [
                    'size' => trim($v['size']),
                    'color' => trim($v['color'] ?? ''),
                    'stock' => (int) $v['stock'],
                    'sort' => $i,
                ];

                $existing = ! empty($v['id'])
                    ? ProductVariant::where('id', $v['id'])->where('product_id', $product->id)->first()
                    : null;

                if ($existing) {
                    $existing->update($attrs);
                } else {
                    ProductVariant::create($attrs + ['product_id' => $product->id]);
                }
            }

            return $product;
        });

        return response()->json(['id' => $saved->id]);
    }
}
