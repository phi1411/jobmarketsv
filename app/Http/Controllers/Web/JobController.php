<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Facades\Config;
use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $html = View::renderWithLayout("jobs/index", [
            "title"       => "Danh Sách Việc Làm Part-Time Sinh Viên | JobMarketplace",
            "currentPage" => "jobs",
            "queryParams" => $request->getParams
        ]);

        return Response::html($html);
    }

    public function show(Request $request, string $id): Response
    {
        $html = View::renderWithLayout("jobs/show", [
            "title"       => "Chi Tiết Việc Làm Part-Time | JobMarketplace",
            "currentPage" => "job_detail",
            "jobId"       => $id,
            "goongMaptilesKey" => Config::goongMaptilesKey()
        ]);

        return Response::html($html);
    }
}
