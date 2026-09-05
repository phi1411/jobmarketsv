<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Http\Request;
use JobMarket\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return Response::success([
            "api_name" => "Job Marketplace Part-time API",
            "version"  => "1.0.0",
            "status"   => "Healthy"
        ], "Hệ thống đang hoạt động bình thường.");
    }
}
