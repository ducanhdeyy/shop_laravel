
    <table class="align-middle mb-0 table table-borderless table-striped table-hover">
        <thead>
        <tr>
            <th class="text-center">ID</th>
            <th class="text-center">Name</th>
            <th class="text-center">Email</th>
            <th class="text-center">address</th>
            <th class="text-center">Phone</th>
            <th class="text-center">PayMent Name</th>
            <th class="text-center">Date</th>
            <th class="text-center">total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($orders as $order)
            <tr>
                <td class="text-center text-muted">{{$order->id}}</td>
                <td class="text-center">{{$order->name}}</td>
                <td class="text-center">{{$order->email}}</td>
                <td class="text-center">{{$order->address}}</td>
                <td class="text-center">{{$order->phone}}</td>
                <td class="text-center">{{$order->payment_name}}</td>
                <td class="text-center">{{date('Y/d/M', strtotime($order->created_at))}}</td>
                <td class="text-center">{{$order->total}}VNĐ</td>
            </tr>
        @endforeach
        </tbody>
    </table>
