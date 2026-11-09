<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewOrderMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function check(bool $ok, string $message): void
    {
        if (! $ok) {
            abort(422, $message);
        }
    }

    // Prices always come from the database, never from the browser. Stock is locked and
    // decremented in the same transaction, so two people can't buy the last piece.
    public function store(Request $request): JsonResponse
    {
        $customer = (array) $request->input('customer', []);
        $name = trim((string) ($customer['name'] ?? ''));
        $phone = preg_replace('/[^0-9+]/', '', (string) ($customer['phone'] ?? ''));
        $email = trim((string) ($customer['email'] ?? ''));
        $city = trim((string) ($customer['city'] ?? ''));
        $address = trim((string) ($customer['address'] ?? ''));
        $notes = mb_substr(trim((string) ($customer['notes'] ?? '')), 0, 500);
        $method = (string) $request->input('payment_method');
        $reference = trim((string) $request->input('payment_reference', ''));
        $items = $request->input('items');

        $this->check(mb_strlen($name) >= 2 && mb_strlen($name) <= 100, 'Please enter your full name');
        $this->check(strlen($phone) >= 7 && strlen($phone) <= 16, 'Please enter a valid phone number');
        $this->check(mb_strlen($email) <= 255, 'Please enter a valid email address');
        $this->check(mb_strlen($city) >= 2 && mb_strlen($city) <= 60, 'Please choose your city');
        $this->check(mb_strlen($address) >= 5 && mb_strlen($address) <= 300, 'Please enter your delivery address');
        $this->check(in_array($method, ['cod', 'whish', 'omt', 'card'], true), 'Please choose a payment method');
        $this->check(is_array($items) && count($items) > 0, 'Your bag is empty');
        $this->check(count($items) <= 20, 'Too many items in one order');
        if (in_array($method, ['whish', 'omt'], true)) {
            $this->check(mb_strlen($reference) >= 3, 'Please enter your transfer reference number');
        }

        StoreSetting::current(); // make sure the settings row exists

        $order = DB::transaction(function () use ($name, $phone, $email, $city, $address, $notes, $method, $reference, $items) {
            // Locking the settings row also hands out the next order number safely.
            $settings = StoreSetting::lockForUpdate()->find(1);

            $this->check(! ($method === 'card' && ! $settings->card_enabled), 'Card payment is not available yet');

            $number = (int) $settings->next_order_number;
            $settings->update(['next_order_number' => $number + 1]);

            $order = Order::create([
                'order_number' => $number,
                'customer_name' => $name,
                'phone' => $phone,
                'email' => $email === '' ? null : $email,
                'city' => $city,
                'address' => $address,
                'notes' => $notes === '' ? null : $notes,
                'payment_method' => $method,
                'payment_status' => in_array($method, ['whish', 'omt'], true) ? 'awaiting_confirmation' : 'unpaid',
                'payment_reference' => $reference === '' ? null : $reference,
                'status' => 'pending',
                'subtotal' => 0,
                'delivery_fee' => 0,
                'total' => 0,
            ]);

            $subtotal = 0.0;

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 0);
                $this->check($qty >= 1 && $qty <= 10, 'Invalid quantity');

                $variantId = (string) ($item['variant_id'] ?? '');
                $variant = Str::isUuid($variantId)
                    ? ProductVariant::with('product')->lockForUpdate()->find($variantId)
                    : null;
                $product = $variant?->product;

                $this->check($variant !== null && $product !== null && $product->is_active, 'An item in your bag is no longer available');
                $this->check($variant->stock >= $qty, "{$product->name} ({$variant->size}) only has {$variant->stock} left");

                $variant->decrement('stock', $qty);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'image' => $product->images[0] ?? null,
                    'unit_price' => $product->price,
                    'quantity' => $qty,
                ]);

                $subtotal += $product->price * $qty;
            }

            $subtotal = round($subtotal, 2);
            $fee = ($settings->free_delivery_over !== null && $subtotal >= $settings->free_delivery_over)
                ? 0.0
                : (float) $settings->delivery_fee;

            $order->update(['subtotal' => $subtotal, 'delivery_fee' => $fee, 'total' => round($subtotal + $fee, 2)]);

            return $order;
        });

        $this->notifyShop($order->id);

        return response()->json(['id' => $order->id, 'order_number' => $order->order_number], 201);
    }

    // Order confirmation page: anyone holding the (unguessable) order id sees a summary,
    // without phone, address or notes.
    public function show(string $id): JsonResponse
    {
        $order = Str::isUuid($id) ? Order::with('orderItems')->find($id) : null;
        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'customer_name' => $order->customer_name,
            'city' => $order->city,
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'created_at' => $order->created_at,
            'order_items' => $order->orderItems->map(fn ($i) => [
                'product_name' => $i->product_name,
                'size' => $i->size,
                'color' => $i->color,
                'image' => $i->image,
                'unit_price' => $i->unit_price,
                'quantity' => $i->quantity,
            ])->values(),
        ]);
    }

    // Email the shop about the new order after the response is sent; a mail problem never fails the order.
    private function notifyShop(string $orderId): void
    {
        $recipients = array_values(array_filter(array_map('trim', explode(',', (string) config('justmove.notify_email')))));
        if (! $recipients) {
            return;
        }

        dispatch(function () use ($orderId, $recipients) {
            try {
                $order = Order::with('orderItems')->find($orderId);
                if ($order) {
                    Mail::to($recipients)->send(new NewOrderMail($order));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }
}
