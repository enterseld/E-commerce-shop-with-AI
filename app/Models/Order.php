<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'first_name',
        'last_name', 
        'middle_name',
        'email',
        'total_price', 
        'status', 
        'session_id', 
        'mobile_phone', 
        'shipping_city', 
        'shipping_warehouse', 
        'created_by', 
        'updated_by'
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    
    function order()  {
        return $this->belongsTo(User::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}