<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_number', 'customer_name', 'phone', 'email', 'city', 'address', 'notes',
        'payment_method', 'payment_status', 'payment_reference', 'status',
        'subtotal', 'delivery_fee', 'total',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'subtotal' => 'float',
        'delivery_fee' => 'float',
        'total' => 'float',
    ];

    // Serialised as "order_items".
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
