<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_fee' => ['required', 'numeric', 'min:0'],
            'free_delivery_over' => ['nullable', 'numeric', 'min:0'],
            'whish_number' => ['nullable', 'string', 'max:100'],
            'omt_name' => ['nullable', 'string', 'max:100'],
            'card_enabled' => ['required', 'boolean'],
        ]);

        StoreSetting::current()->update([
            'delivery_fee' => $data['delivery_fee'],
            'free_delivery_over' => $data['free_delivery_over'] ?? null,
            'whish_number' => $data['whish_number'] ?? '',
            'omt_name' => $data['omt_name'] ?? '',
            'card_enabled' => $data['card_enabled'],
        ]);

        return response()->json(['ok' => true]);
    }
}
