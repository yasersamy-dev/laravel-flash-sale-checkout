<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\PaymentWebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $idempotencyKey = $request->input('idempotency_key');
        $orderId        = $request->input('order_id');
        $status         = $request->input('status'); // paid / failed

        return DB::transaction(function () use ($idempotencyKey, $orderId, $status, $request) {

            // check duplicate webhook
            if (PaymentWebhookLog::where('idempotency_key', $idempotencyKey)->exists()) {
                return response()->json(['message' => 'Duplicate webhook ignored'], 200);
            }

            // log webhook first
            $log = PaymentWebhookLog::create([
                'idempotency_key' => $idempotencyKey,
                'payload'         => $request->all(),
            ]);

            $order = Order::lockForUpdate()->find($orderId);

            // order not created yet
            if (! $order) {
                return response()->json(['message' => 'Order not ready yet'], 202);
            }

            $hold = $order->hold()->lockForUpdate()->first();
            $product = $hold->product;

            if ($status === 'paid') {

                $order->update(['status' => 'paid']);

                $log->update(['processed_at' => now()]);

                // clear cache
                Cache::forget("product_{$product->id}");

                return response()->json(['message' => 'Payment confirmed'], 200);
            }

            if ($status === 'failed') {

                $order->update(['status' => 'cancelled']);

                // return stock because payment failed
                if ($hold->status === 'used') {
                    $product->increment('stock', $hold->qty);
                }

                // expire hold
                $hold->update(['status' => 'expired']);

                $log->update(['processed_at' => now()]);

                Cache::forget("product_{$product->id}");

                return response()->json(['message' => 'Payment failed, stock restored'], 200);
            }

        });
    }
}
