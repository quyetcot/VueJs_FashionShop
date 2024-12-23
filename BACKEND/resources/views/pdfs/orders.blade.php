<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .order-header {
            margin-bottom: 20px;
        }

        .order-header p {
            margin: 5px 0;
        }

        .order-details-table th {
            text-align: center;
        }

        .order-details-table td {
            text-align: center;
        }

        .order-summary {
            font-weight: bold;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <h1 style="text-align: center">Danh sách đơn hàng</h1>
    @foreach ($orders as $order)
        <div class="order-header">
            <h2>Order #{{ $order->id }} - {{ $order->order_code }}</h2>
            <p><strong>Customer:</strong> {{ $order->user_name }}</p>
            <p><strong>Email:</strong> {{ $order->user_email }}</p>
            <p><strong>Phone:</strong> {{ $order->user_phonenumber }}</p>
            <p><strong>Address:</strong> {{ $order->user_address }}</p>
            <p><strong>Order Status:</strong> {{ ucfirst($order->order_status) }}</p>
            <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('d-m-Y') }}</p>
            <p><strong>Total Amount:</strong> {{ number_format($order->total, 2) }}VND</p>
        </div>

        <h3>Chi tiết đơn hàng:</h3>
        <table class="order-details-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Product Name</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Quantity</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Product Price</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Attributes</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Product Image</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Total Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->orderDetails as $detail)
                    <tr>
                        <!-- Product Name -->
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $detail->product_name }}</td>

                        <!-- Quantity -->
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ $detail->quantity }}</td>

                        <!-- Unit Price -->
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($detail->price, 2) }}VND
                        </td>

                        <!-- Attributes (Size and Color or other attributes) -->
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            @if ($detail->attributes)
                                @foreach ($detail->attributes as $attribute => $value)
                                    <strong>{{ ucfirst($attribute) }}:</strong> {{ $value }} <br>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>

                        <!-- Product Image -->
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <img src="{{ $detail->product_img }}" alt="{{ $detail->product_name }}"
                                style="width: 50px; height: auto;">
                        </td>

                        <!-- Total Price -->
                        <td style="border: 1px solid #ddd; padding: 8px;">{{ number_format($detail->total_price, 2) }}
                            VND</td>
                    </tr>
                @endforeach
            </tbody>
        </table>


        <div class="order-summary">
            <p><strong>Total Order Amount:</strong> {{ number_format($order->total, 2) }} VND</p>
        </div>
        <hr>
    @endforeach
</body>

</html>
