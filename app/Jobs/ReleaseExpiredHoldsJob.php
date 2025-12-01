<?php

namespace App\Jobs;

use App\Models\Hold;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReleaseExpiredHoldsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DB::transaction(function () {

            $expiredHolds = Hold::where('status', 'active')
                ->where('expires_at', '<', now())
                ->lockForUpdate()
                ->get();

            foreach ($expiredHolds as $hold) {

                $product = $hold->product;

                // return stock
                $product->increment('stock', $hold->qty);

                // update hold
                $hold->update(['status' => 'expired']);

                // clear cache
                Cache::forget("product_{$product->id}");
            }

        });
    }
}
