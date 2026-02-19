<?php

namespace App\Services;

class CartService
{
    public static function get()
    {
        return session()->get('cart', []);
    }

    public static function add($item)
    {
        $cart = self::get();

        // unique key = plan + addons
        $key = $item['plan_id'] . '_' . md5(json_encode($item['addons']));

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $item['quantity'];
            $cart[$key]['total'] += $item['total'];
        } else {
            $cart[$key] = $item;
        }

        session()->put('cart', $cart);
    }

 public static function update(string $key, array $data)
    {
        $cart = self::get();
        if (!isset($cart[$key])) return;

        $cart[$key] = array_replace_recursive($cart[$key], $data);

        session()->put('cart', $cart);
    }

    public static function remove(string $key)
    {
        $cart = self::get();
        unset($cart[$key]);
        session()->put('cart', $cart);
    }


    public static function total()
    {
        return collect(self::get())->sum('total');
    }

     public static function count()
    {
        return count(self::get());
    }
    public static function clear()
{
    session()->forget('cart');
}

}



