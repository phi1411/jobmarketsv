<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return [
            "data" => "is here"
        ];
    }
}
