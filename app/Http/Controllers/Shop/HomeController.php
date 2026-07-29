<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\GetHomePageDataAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(GetHomePageDataAction $getHomePageData): View
    {
        return view('shop.home.index', $getHomePageData->execute());
    }
}
