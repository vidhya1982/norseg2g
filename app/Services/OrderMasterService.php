<?php

namespace App\Services;

use App\Models\OrderMaster;

class OrderMasterService
{
    public function create($userId, $orderType, $cart)
    {
        $orderId = 'ORD' . rand(10, 99) . uniqid();

        return OrderMaster::create([
            'order_id' => $orderId,
            'amount' => collect($cart)->sum('total'),
            'user_id' => $userId,
            'order_type' => $orderType,
            'plans_details' => json_encode($cart),
            'origin' => json_encode(request()->headers->all()),
            'order_status' => 'initiated',
            'payment_status' => 'pending',
        ]);
    }
}