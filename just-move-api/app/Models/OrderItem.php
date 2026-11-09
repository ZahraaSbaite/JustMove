<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'product_name', 'size', 'color', 'image', 'unit_price', 'quantity',
    ];

    protected $casts = ['unit_price' => 'float', 'quantity' => 'integer'];
}
