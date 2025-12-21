<?php

namespace App\Http\Controllers\User;

use App\Helper\Cart;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use function Laravel\Prompts\error;

class OrdersController extends Controller
{

    public function index()
    {
        $orders = Order::with('orderItems.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/OrdersList/OrdersListLayout', [
            'orders' => $orders,
        ]);
    }

    public function show($id)
    {
        $order = Order::where('created_by', Auth::user()->id)
            ->where('id', $id)
            ->with('orderItems.product')
            ->firstOrFail();

        return response()->json([
            'order' => $order,
        ]);
    }

    public function store(Request $request)
    {
        $products = json_decode($request->products, true);
        $order = new Order();
        $order->first_name = $request->first_name;
        $order->last_name = $request->last_name;
        $order->middle_name = $request->middle_name;
        if ($request->email) {
            $order->email = $request->email;
            $order->total_price = $request->total_price;
            $order->status = "Open";
            $order->session_id = "1";
            $order->mobile_phone = $request->mobile_phone;
            $order->shipping_city = $request->shipping_city;
            $order->shipping_warehouse = $request->shipping_warehouse;
            try{
            $order->save();
            
            foreach ($products as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'vendor_code' => $product['vendor_code'],
                    'unit_price' => "200",
                ]);
            }
            Cart::deleteCookieCartItems();

            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
            ]);
        }
        catch(Exception $e){
            return response()->json([
                'err' => $e
            ]);
        }
        } else {
            $order->total_price = $request->total_price;
            $order->status = "Open";
            $order->session_id = "1";
            $order->mobile_phone = $request->mobile_phone;
            $order->shipping_city = $request->shipping_city;
            $order->shipping_warehouse = $request->shipping_warehouse;
            $order->created_by = Auth::user()->id;
            $order->updated_by = Auth::user()->id;
            $order->save();


            foreach ($products as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'vendor_code' => $product['vendor_code'],
                    'unit_price' => "200",
                ]);
            }

            CartItem::where(['user_id' => Auth::user()->id])->delete();
            Cart::deleteCookieCartItems();

            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
            ]);
        }
    }
}
