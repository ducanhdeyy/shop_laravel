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
use Illuminate\Support\Facades\Mail;

class CheckOutController extends Controller
{
    public function index()
    {
        // dd(Auth::guard('cus')->user());
        if (Auth::guard('cus')->user()) {
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
            if ($request->payment_name == 'vnpay_payment') {
                //              Gọi Hàm Thanh Toán VNPay
                $this->VNPay_payment($order->id, $order->total);
            } elseif ($request->payment_name == 'momo_payment') {
                //                Gọi Hàm thanh toán MOMO
                $this->momo_payment($order->total);
            }
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
            //                xóa giỏ hàng
            Cart::destroy();
            DB::commit();
            return redirect()->route('product_cart')->with('success', 'Your order has been successfully placed ');
        } catch (\Exception $err) {
            DB::rollBack();
            Log::error('Message' . $err->getMessage() . 'Line :' . $err->getLine());
            return redirect()->back()->with('error', 'Your order Failed ');
        }
    }

    //    thanh toan VNPay

    public function VNPay_payment($orderId, $total)
    {
        $vnp_Url = " https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://127.0.0.1:8000/checkout";
        $vnp_TmnCode = "X9RKUQ0A"; //Mã website tại VNPAY
        $vnp_HashSecret = "DTMVMYEOIANHSUTLCVTUZGWIGTEWNJTG"; //Chuỗi bí mật

        $vnp_TxnRef = $orderId; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_OrderInfo = 'Nội dung thanh toán';
        $vnp_OrderType = 'BillPayment';
        $vnp_Amount = $total * 100;
        $vnp_Locale = 'VN';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        $returnData = array(
            'code' => '00', 'message' => 'success', 'data' => $vnp_Url
        );
        if (isset($_POST['redirect'])) {
            header('Location: ' . $vnp_Url);
            die();
        } else {
            echo json_encode($returnData);
        }
        // vui lòng tham khảo thêm tại code demo

    }

    //    thanh toán bằng momo
    function execPostRequest($url, $data)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //execute post

        $result = curl_exec($ch);

        //close connection
        curl_close($ch);
        return $result;
    }

    //     public function momo_payment($total)
    //     {
    //         $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";


    //         $partnerCode = 'MOMOBKUN20180529';
    //         $accessKey = 'klm05TvNBzhg7h7j';
    //         $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
    //         $orderInfo = "Thanh toán qua ATM MoMo";
    //         $amount = $total;
    //         $orderId = time() . "";
    //         $redirectUrl = "http://127.0.0.1:8000/checkout";
    //         $ipnUrl = "http://127.0.0.1:8000/checkout";
    //         $extraData = "";
    //         $requestId = time() . "";
    //         $requestType = "payWithATM";
    // //            $extraData = ($_POST["extraData"] ? $_POST["extraData"] : "");
    //         //before sign HMAC SHA256 signature
    //         $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
    //         $signature = hash_hmac("sha256", $rawHash, $secretKey);
    //         $data = array('partnerCode' => $partnerCode,
    //             'partnerName' => "Test",
    //             "storeId" => "MomoTestStore",
    //             'requestId' => $requestId,
    //             'amount' => $amount,
    //             'orderId' => $orderId,
    //             'orderInfo' => $orderInfo,
    //             'redirectUrl' => $redirectUrl,
    //             'ipnUrl' => $ipnUrl,
    //             'lang' => 'vi',
    //             'extraData' => $extraData,
    //             'requestType' => $requestType,
    //             'signature' => $signature);

    //         $result = $this->execPostRequest($endpoint, json_encode($data));
    // //        dd($result);

    //         $jsonResult = json_decode($result, true);  // decode json
    //         //Just a example, please check more in there
    //         return redirect()->to($jsonResult['payUrl']);
    //     }
}
