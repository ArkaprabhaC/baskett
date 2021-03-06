<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Product;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Hesto\MultiAuth\Traits\LogsoutGuard;
use Illuminate\Http\Request;
use Session;
use Carbon\Carbon;

class ProductController extends Controller
{
    protected function viewIndex(Request $request){
        
        $products = Product::all();
        $datediff=array();
    

        if(Auth::guard('customer')->check()){
            $cust_id = Auth::guard('customer')->user()->id;

            //get the tuples of all the cart products for the current authenticated customer
            $get_tuples_no = count(Cart::where(['custID'=>$cust_id])->get());
            if($get_tuples_no > 0){
                $request->session()->put('cartproducts', $get_tuples_no);
            }else{
                $request->session()->put('cartproducts', 0);
            }
        }

        //For date calculation using the Carbon library
        foreach($products as $prod){
            $datediff[$prod->id] = $this->datecal($prod->id);
        }
        return view ('index',['products' => $products, 'datediff' => $datediff]);
    }
    protected function addToCart(Request $request, $id)
    {
        if(Auth::guard('customer')->check()){
            
            $cust_id = Auth::guard('customer')->user()->id;

            $product = Product::find($id);

            $cart = new Cart();
    
            $cart->productname = $product->name;
            $cart->productprice = $product->price;
            $cart->custID = $cust_id;
            $cart->save();

            return redirect('/')->with('alertsuccess', 'Product added to your cart!');
        }else{
            return redirect('/')->with('alerterror', 'you must be logged in to add this product to cart.');
        }
       
        
    }

    protected function viewCart(){
        
        if(Auth::guard('customer')->check()){
            $cust_id = Auth::guard('customer')->user()->id;
            $get_cart = Cart::where(['custID'=>$cust_id])->get()->toArray();
            
            $totalPrice = Cart::where('custID',$cust_id)->sum('productprice');
            
            return view('cart',['cartproducts' => $get_cart, 'totalPrice' => $totalPrice]);
        }else{
            return redirect('/');
        }
    }

    protected function deleteCartItem(Request $request, $id){
       
        if(Auth::guard('customer')->check()){
            $cust_id = Auth::guard('customer')->user()->id;
            Cart::where(['id' => $id, 'custID' => $cust_id])->delete();
            


            //get the tuples of all the cart products for the current authenticated customer
            $get_tuples_no = count(Cart::where(['custID'=>$cust_id])->get());
            if($get_tuples_no > 0){
                $request->session()->put('cartproducts', $get_tuples_no);
            }else{
                $request->session()->put('cartproducts', 0);
            }

            return redirect('/cart')->with('alertsuccess', 'Item deleted from cart');
        }else{
            return redirect('/');
        }
    }

    /**
     * Days remaining to expire of a product using the Carbon library
     */
    protected function datecal($id)
    {
        $ed=Product::all();

        $today=Carbon::today();
        $prod=Product::find($id);
        $expd=date_create($prod->expirydate);
        $datediff=date_diff($expd,$today);
        return $datediff;
        
    }
}
