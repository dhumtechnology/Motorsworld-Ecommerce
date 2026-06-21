<?php

namespace App\Enums\Products;

enum ProductStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Disabled = 'disabled';
    case Locked = 'locked';
}
