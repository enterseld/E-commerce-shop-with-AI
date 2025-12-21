<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $orders = Order::where('status', 'Paid')->get();

        return Inertia::render('Admin/Dashboard', [
            'orders' => $orders,
        ]);
    }
}
