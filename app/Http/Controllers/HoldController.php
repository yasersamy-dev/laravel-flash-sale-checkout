<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hold;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HoldController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($request) {

            $product = Product::lockForUpdate()->find($request->product_id);

            if ($product->stock < $request->qty) {
                return response()->json(['message' => 'Not enough stock'], 422);
            }

            // قلّل stock
            $product->stock -= $request->qty;
            $product->save();

            // اعمل hold
            $hold = Hold::create([
                'product_id' => $product->id,
                'qty'        => $request->qty,
                'expires_at' => Carbon::now()->addMinutes(2),
                'status'     => 'active',
            ]);

            // clear cache
            Cache::forget("product_{$product->id}");

            return response()->json([
                'hold_id'    => $hold->id,
                'expires_at' => $hold->expires_at
            ]);

        }, 5); // retry 5 times
    }
}
