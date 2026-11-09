<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();                        // unguessable, used in the confirmation link
            $table->unsignedBigInteger('order_number')->unique(); // human number, starts at 1001
            $table->string('customer_name', 100);
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('city', 60);
            $table->string('address', 300);
            $table->text('notes')->nullable();
            $table->enum('payment_method', ['cod', 'whish', 'omt', 'card']);
            $table->enum('payment_status', ['unpaid', 'awaiting_confirmation', 'paid', 'refunded'])->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
