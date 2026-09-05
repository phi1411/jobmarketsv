<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $html = View::renderWithLayout("home", [
            "title"       => "Tìm Việc Làm Part-Time Sinh Viên | JobMarketplace",
            "currentPage" => "home"
        ]);

        return Response::html($html);
    }
}
