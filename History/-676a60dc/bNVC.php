<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $products = DB::table('products')->get();
        dd($products); 
        return view('home');
    }
    
    /**
     * Add the product
     *
     * @return void
     */
    public function add()
    {
        return view('product/add');
    }

    public function do_add($request)
    {
        dd($request);
    }
}
