<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $table = 'store_settings';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id', 'delivery_fee', 'free_delivery_over', 'whish_number', 'omt_name', 'card_enabled', 'next_order_number',
    ];

    // Only the five fields the storefront needs are sent to the browser.
    protected $hidden = ['id', 'next_order_number', 'created_at', 'updated_at'];

    protected $casts = [
        'delivery_fee' => 'float',
        'free_delivery_over' => 'float',
        'card_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'delivery_fee' => 4,
            'whish_number' => '',
            'omt_name' => '',
            'card_enabled' => false,
            'next_order_number' => 1001,
        ]);
    }
}
