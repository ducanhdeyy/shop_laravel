<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
        integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('template/admin/css/style.css') }}">
</head>

<body>

    <div class = "invoice-wrapper" id = "print-area">
        <div class = "invoice">
            <div class = "invoice-container">
                <div class = "invoice-head">
                    <div class = "invoice-head-top">
                        <div class = "invoice-head-top-left text-start">
                            <img src = "images/logo.png">
                        </div>
                        <div class = "invoice-head-top-right text-end">
                            <h3>Code Lean</h3>
                        </div>
                    </div>
                    <div class = "hr"></div>
                    <div class = "invoice-head-middle">
                        <div class = "invoice-head-middle-left text-start">
                            <p><span class = "text-bold">Date</span>: {{date('M d Y', strtotime($order->created_at))}}</p>
                        </div>
                    </div>
                    <div class = "hr"></div>
                    <div class = "invoice-head-bottom">
                        <div class = "invoice-head-bottom-left">
                            <ul>
                                <li class = 'text-bold'>Store:</li>
                                <li>Code Lean</li>
                                <li>Sóc Sơn, Hà Nội</li>
                                <li>+84 368 351 35</li>
                                <li>Việt Nam</li>
                            </ul>
                        </div>
                        <div class = "invoice-head-bottom-right">
                            <ul class = "text-end">
                                <li class = 'text-bold'>Pay To:</li>
                                <li>{{$order->name}}</li>
                                <li>{{$order->phone}}</li>
                                <li>{{$order->email}}</li>
                                <li>{{$order->address}}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class = "overflow-view">
                    <div class = "invoice-body">
                        <table>
                            <thead>
                                <tr>
                                    <td class = "text-bold">Name</td>
                                    <td class = "text-bold">Color</td>
                                    <td class = "text-bold">Size</td>
                                    <td class = "text-bold">Quantity</td>
                                    <td class = "text-bold">Price</td>
                                    <td class = "text-bold">Total</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalOrder = 0; ?>
                                @foreach ($order->orderDetails as $orderDetail)
                                    <tr>
                                        <td>{{$orderDetail->product->name}}</td>
                                        <td>{{$orderDetail->color}}</td>
                                        <td>{{$orderDetail->size}}</td>
                                        <td>{{$orderDetail->quantity}}</td>
                                        <td>{{number_format($orderDetail->price)}} VND</td>
                                        <td class = "text-end">{{ number_format($orderDetail->total)}} VND</td>
                                        {{--           Tính tông giá đơn hàng --}}
                                        <?php $totalOrder += $orderDetail->total; ?>
                                    </tr>
                                    @endforeach
                            </tbody>
                        </table>
                        <div class = "invoice-body-bottom">
                            <div class = "invoice-body-info-item">
                                <div class = "info-item-td text-end text-bold">Total:</div>
                                <div class = "info-item-td text-end">{{number_format($totalOrder)}} VND</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "invoice-foot text-center">
                    <p><span class = "text-bold text-center">NOTE:&nbsp;</span>Thank you for buy our product.</p>
                    <div class = "invoice-btns">
                        <button type = "button" class = "invoice-btn" onclick="printInvoice()">
                            <span>
                                <i class="fa-solid fa-print"></i>
                            </span>
                            <span>Print</span>
                        </button>
                        <button type = "button" class = "invoice-btn">
                            <span>
                                <i class="fa-solid fa-download"></i>
                            </span>
                            <span>Download</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('template/admin/js/script.js') }}"></script>
</body>

</html>
