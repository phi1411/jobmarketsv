<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class CvTemplateWebController extends Controller
{
    public function index(Request $request): Response
    {
        $html = View::renderWithLayout("cv/templates", [
            "title"       => "Mẫu CV Sinh Viên Chuẩn ATS & Part-Time | JobMarketSV",
            "currentPage" => "cv_templates"
        ]);

        return Response::html($html);
    }
}
