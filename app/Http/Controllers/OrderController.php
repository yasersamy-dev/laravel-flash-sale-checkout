<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'hold_id' => 'required|exists:holds,id',
        ]);

        return DB::transaction(function () use ($request) {

            $hold = Hold::where('id', $request->hold_id)
                ->lockForUpdate()
                ->first();

            if (! $hold) {
                return response()->json(['message' => 'Hold not found'], 404);
            }

            if ($hold->status !== 'active') {
                return response()->json(['message' => 'Hold is not active'], 400);
            }

            if ($hold->expires_at->isPast()) {
                return response()->json(['message' => 'Hold expired'], 400);
            }

            $order = Order::create([
                'hold_id' => $hold->id,
                'status'  => 'pending_payment',
            ]);

            $hold->update(['status' => 'used']);

            // clear cache
            Cache::forget("product_{$hold->product_id}");

            return response()->json([
                'message' => 'Order created',
                'order'   => $order
            ], 201);

        }, 5); // retry
    }
}
