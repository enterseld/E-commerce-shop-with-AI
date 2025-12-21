<?php

namespace App\Http\Controllers\User;

use App\Helper\Cart;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\UserAdress;
use App\Services\NovaPostService as ServicesNovaPostService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CartController extends Controller
{
    protected $novaPoshtaService;

    public function __construct(ServicesNovaPostService $novaPoshtaService)
    {
        $this->novaPoshtaService = $novaPoshtaService;
    }

    public function view(Request $request, Product $product)
    {

        $user = Auth::user();

        if ($user) {
            $cartItems = CartItem::where('user_id', $user->id)->get();
            $userAdress = UserAdress::where('user_id', $user->id)->where('isMain', 1)->first();

            if ($cartItems->count() > 0) {

                return Inertia::render('User/CartList', [
                    'cartItems' => $cartItems,
                    'userAdress' => $userAdress,
                    
                ]);
            }
        } else {
            $cartItems = Cart::getCookieCartItems();
            if (count($cartItems) > 0) {
                $cartItems = new CartResource(Cart::getProductsAndCartItems());
                return Inertia::render('User/CartList', [
                    'cartItems' => $cartItems,

                ]);
            } else {
                return redirect()->back();
            }
        }
    }

    public function getCities(Request $request, string $findBy)
    {   
        
        $response = $this->novaPoshtaService->makeRequestCities('Address', 'getCities', $findBy);
        $cities = $response['data'] ?? [];
        
        // Отримати лише назви міст
        $cityNames = array_map(function ($city, $index) {
            return [
                'id' => $index, // You can use any unique identifier (e.g., a database ID, or $index)
                'title' => $city['Description']
            ];
        }, $cities, array_keys($cities));

        return response()->json(['cities' => $cityNames]);
    }

    public function getWarehouses(Request $request, string $city, string $findBy)
    {   
        if($city == "0"){
            $response = $this->novaPoshtaService->makeRequestAllWarehouses('Address', 'getWarehouses', $findBy);
            $warehouses = $response['data'] ?? [];
        }
        else{
            $response = $this->novaPoshtaService->makeRequestWarehouses('Address', 'getWarehouses', $city, $findBy);
            $warehouses = $response['data'] ?? [];
        }
        // Отримати лише назви відділень
        $warehouseNames = array_map(function ($warehouse, $index) {
            return [
                'id' => $index, 
                'title' => $warehouse['Description']
            ];
        }, $warehouses, array_keys($warehouses));

        return response()->json(['warehouses' => $warehouseNames]);
        
    }

    public function store(Request $request, Product $product)
    {
        $quantity = $request->post('quantity', 1);
        $user = Auth::user();

        if ($user) {
            $cartItem = CartItem::where(['user_id' => $user->id, 'product_id' => $product->id])->first();
            if ($cartItem) {
                $cartItem->increment('quantity');
            } else {
                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'vendor_code' => $product->vendor_code,
                ]);
            }
        } else {
            $cartItems = Cart::getCookieCartItems();
            $isProductExists = false;
            foreach ($cartItems as $item) {
                if ($item['product_id'] === $product->id) {
                    $item['quantity'] += $quantity;
                    $item['vendor_code'] = $product->vendor_code;
                    $isProductExists = true;
                    break;
                }
            }

            if (!$isProductExists) {
                $cartItems[] = [
                    'user_id' => null,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'vendor_code' => $product->vendor_code,
                ];
            }
            Cart::setCookieCartItems($cartItems);
        }
        return redirect()->back()->with('success', 'cart added successfully');
    }

    public function update(Request $request, Product $product)
    {
        $quantity = $request->integer('quantity');
        $user = Auth::user();

        if ($user) {
            CartItem::where(['user_id' => $user->id, 'product_id' => $product->id])->update(['quantity' => $quantity], ['vendor_code' => $product->vendor_code]);
        } else {
            $cartItems = Cart::getCookieCartItems();
            foreach ($cartItems as &$item) {
                if ($item['product_id'] == $product->id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
            Cart::setCookieCartItems($cartItems);
        }
        return redirect()->back();
    }


    public function delete(Request $request, Product $product)
    {
        $user = Auth::user();
        if ($user) {
            CartItem::query()->where(['user_id' => $user->id, 'product_id' => $product->id])->first()?->delete();
            if (CartItem::count() <= 0) {
                return redirect()->route('user.home')->with('info', 'your cart is empty');
            } else {
                return redirect()->back()->with('success', 'item removed successfully');
            }
        } else {
            $cartItems = Cart::getCookieCartItems();
            foreach ($cartItems as $i => &$item) {
                if ($item['product_id'] == $product->id) {
                    array_splice($cartItems, $i, 1);
                    break;
                }
            }
            Cart::setCookieCartItems($cartItems);
            if (count($cartItems) <= 0) {
                return redirect()->route('user.home')->with('info', 'your cart is empty');
            } else {
                return redirect()->back()->with('success', 'item removed successfully');
            }
        }
    }
}
