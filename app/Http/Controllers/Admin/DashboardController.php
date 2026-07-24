<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Dashboard\GetDashboardMetricsAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(GetDashboardMetricsAction $metrics): View
    {
        return view('admin.dashboard.index', $metrics());
    }
}
