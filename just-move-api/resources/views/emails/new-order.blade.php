@php
    $labels = ['cod' => 'Cash on delivery', 'whish' => 'Whish Money', 'omt' => 'OMT', 'card' => 'Card'];
    $money = fn ($n) => '$' . number_format((float) $n, 2);
@endphp
<h2>New order #{{ $order->order_number }}</h2>
<p><b>{{ $order->customer_name }}</b><br>{{ $order->phone }}<br>{{ $order->city }} — {{ $order->address }}</p>
@if($order->notes)
<p><i>Note:</i> {{ $order->notes }}</p>
@endif
<p>Payment: <b>{{ $labels[$order->payment_method] ?? $order->payment_method }}</b>
@if($order->payment_reference)
 — ref <b>{{ $order->payment_reference }}</b> (check before confirming)
@endif
</p>
<table cellpadding="6" style="border-collapse:collapse" border="1">
    <tr><th align="left">Item</th><th align="left">Size</th><th>Qty</th><th align="right">Total</th></tr>
    @foreach($order->orderItems as $i)
    <tr>
        <td>{{ $i->product_name }}</td>
        <td>{{ $i->size }}@if($i->color) / {{ $i->color }}@endif</td>
        <td align="center">{{ $i->quantity }}</td>
        <td align="right">{{ $money($i->unit_price * $i->quantity) }}</td>
    </tr>
    @endforeach
</table>
<p>Subtotal {{ $money($order->subtotal) }} · Delivery {{ $money($order->delivery_fee) }} · <b>Total {{ $money($order->total) }}</b></p>
<p><a href="{{ rtrim(config('justmove.site_url'), '/') }}/admin/orders/{{ $order->id }}">Open in admin</a></p>
