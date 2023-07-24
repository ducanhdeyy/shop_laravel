<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddOrderRequest;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckOutController extends Controller
{
    public function index()
    {
        if (Auth::user()) {
            $customer = Auth::guard('cus')->user();
            $carts = Cart::content();
            $total = Cart::total();
            $total = str_replace('.00', '', $total);
            $subTotal = Cart::subtotal();
            $subTotal = str_replace('.00', '', $subTotal);
            return view('client.checkout.index', compact('carts', 'subTotal', 'total', 'customer'));
        } else {
            return redirect()->back()->with('error', 'You Must Login Before Paying');
        }
    }

    public function addOrder(AddOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            $total = $request->total;
            $total = str_replace(',', '', $total);
            $dataOrder = [
                'customer_id' => $request->customer_id,
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'phone' => $request->phone,
                'status' => $request->status,
                'payment_name' => $request->payment_name,
                'total' => $total
            ];
            $order = Order::create($dataOrder);

            $carts = Cart::content();


//        thêm vào bảng order_detail
            foreach ($carts as $cart) {
                $data = [
                    'product_id' => $cart->id,
                    'order_id' => $order->id,
                    'quantity' => $cart->qty,
                    'price' => $cart->price,
                    'total' => $cart->price * $cart->qty,
                    'color' => $cart->options->color,
                    'size' => $cart->options->size
                ];
//                trừ đi số hàng tồn kho
                $product = Product::find($cart->id);
                $newAmount = $product->amount - $cart->qty;
                if ($newAmount <= 0) {
                    return redirect()->back()->with('error', 'The product in stock is out of stock! Please choose another product');
                }
                $product->update(['amount' => $newAmount]);

                Order_detail::create($data);
            }
            //         xóa giỏ hàng
            Cart::destroy();
            DB::commit();
            return redirect()->route('product_cart')->with('success', 'Your order has been successfully placed ');
        } catch (\Exception $err) {
            DB::rollBack();
            Log::error('Message' . $err->getMessage() . 'Line :' . $err->getLine());
            return redirect()->back()->with('error', 'Your order Failed ');
        }
    }



}
