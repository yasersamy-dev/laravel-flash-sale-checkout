<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Hold;
use Illuminate\Support\Facades\Cache;


class ProductController extends Controller
{
    public function index($id)
    {
        // Cache for 1 second (flash-sale safe)
        return Cache::remember("product_$id", 1, function () use ($id) {

            $product = Product::findOrFail($id);

           
            $activeHolds = Hold::where('product_id', $id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->sum('qty');

            $available = $product->stock - $activeHolds;

            return [
                'id'              => $product->id,
                'name'            => $product->name,
                'price'           => $product->price,
                'available_stock' => $available,
            ];
        });
    }
}
