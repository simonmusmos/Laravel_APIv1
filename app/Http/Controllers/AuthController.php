<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;

class AuthController extends Controller
{
    public $loginAfterSignUp = true;

    public function register(Request $request)
    {
        $query = DB::table('users')
            ->select(array(DB::raw('COUNT(1) as same_email')))
            ->where('email', '=', $request['email'])
            ->first();
        if($query->same_email < 1){
            $user = User::create([
                'email' => $request->email,
                'password' => bcrypt($request->password),
              ]);
        
        
              return response()->json(['message'=> 'User Successfully Registered'],201); 
        }else{
            return response()->json(['message'=> 'Email already taken'],400); 
        }
      
    }

    public function login(Request $request)
    {
      $credentials = $request->only(['email', 'password']);

      if (!$token = auth()->attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
      }
      $query = DB::table('users')
            ->select('id')
            ->where('email', '=', $request['email'])
            ->first();
      DB::table('user_session')->insert([
          ['user' => $query->id, 'value' => $token, 'date' => date("Y-m-d H:i:s")]
      ]);
      return $this->respondWithToken($token);
    }
    protected function respondWithToken($token)
    {
      return response()->json([
        'access_token' => $token
      ], 201);
    }
    public function order(Request $request)
    {
        $header = $request->header('Authorization');
        $query = DB::table('user_session')
            ->select(array(DB::raw('COUNT(1) as cnt')))
            ->where('value', '=', $header)
            ->first();
        if($query->cnt > 0){
          $product_info = DB::table('products')
            ->select(DB::raw('COUNT(1) as products'))
            ->where('id', '=', $request['product_id'])
            ->first();
          if($product_info->products > 0){
            $quantity_info = DB::table('products')
              ->select('quantity')
              ->where('id', '=', $request['product_id'])
              ->first();
            if($quantity_info->quantity >= $request['quantity']){
              $newqty=$quantity_info->quantity - $request["quantity"];
              DB::table('products')
                ->where('id', $request['product_id']) 
                ->update(array('quantity' => $newqty));  

                return response()->json(['message'=>"You have successfully ordered this product."],200);
            }else{
              return response()->json(['message'=>"Failed to order this product due to unavailability of the stock"], 400);
            }
          }else{
            return response()->json(['message'=>"Product does not Exist!"], 400);
          }
          
        }else{
          return response()->json(['message'=>"user is not logged in"], 401);
        }
        
    }
    public function addProduct(Request $request)
    {
        $header = $request->header('Authorization');
        $query = DB::table('user_session')
            ->select(array(DB::raw('COUNT(1) as cnt')))
            ->where('value', '=', $header)
            ->first();
        if($query->cnt > 0){
          $product_info = DB::table('products')
            ->select(DB::raw('COUNT(1) as products'))
            ->where('name', '=', $request['name'])
            ->first();
          if($product_info->products > 0){
            $quantity_info = DB::table('products')
              ->select('quantity')
              ->where('name', '=', $request['name'])
              ->first();
            $newqty=$quantity_info->quantity + $request["quantity"];
            DB::table('products')
              ->where('name', $request['name']) 
              ->update(array('quantity' => $newqty)); 

            return response()->json(['message'=>"You have added product quantity."],200);
            
          }else{
            DB::table('products')->insert([
              ['name' => $request['name'], 'quantity' => $request['quantity']]
            ]);
          }
          
        }else{
          return response()->json(['message'=>"user is not logged in"], 401);
        }
        
    }
    public function showProducts(Request $request)
    {
        $header = $request->header('Authorization');
        $query = DB::table('user_session')
            ->select(array(DB::raw('COUNT(1) as cnt')))
            ->where('value', '=', $header)
            ->first();
        if($query->cnt > 0){
          $products = DB::table('products')
            ->select("*")
            ->get();
            return response()->json([$products], 401);
        }else{
          return response()->json(['message'=>"user is not logged in"], 401);
        }
        
    }
}
