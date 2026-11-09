<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminUploadController extends Controller
{
    // Product photos go to storage/app/public/product-images (served from /storage after `php artisan storage:link`).
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);

        $path = $request->file('file')->store('product-images', 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
