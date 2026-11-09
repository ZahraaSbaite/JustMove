<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table (id is always 1).
        Schema::create('store_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->decimal('delivery_fee', 10, 2)->default(4);
            $table->decimal('free_delivery_over', 10, 2)->nullable(); // null = never free
            $table->string('whish_number')->default('');
            $table->string('omt_name')->default('');
            $table->boolean('card_enabled')->default(false);
            $table->unsignedBigInteger('next_order_number')->default(1001);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
