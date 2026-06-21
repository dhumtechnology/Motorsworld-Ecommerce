<?php

namespace App\Enums\Auth;

enum TokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
}
