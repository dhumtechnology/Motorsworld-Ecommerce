<?php

namespace App\Enums\Orders;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
