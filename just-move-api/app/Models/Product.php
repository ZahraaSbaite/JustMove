<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'slug', 'description', 'gender', 'category_id', 'price', 'compare_at_price',
        'images', 'styles', 'is_active', 'is_featured', 'created_at',
    ];

    protected $casts = [
        'price' => 'float',
        'compare_at_price' => 'float',
        'images' => 'array',
        'styles' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Serialised as "product_variants" (what the React app expects).
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort');
    }
}
