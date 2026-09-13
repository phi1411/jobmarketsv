<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Domain\CvBuilder\CvHtmlRenderer;
use JobMarket\Domain\CvBuilder\OnlineCvService;
use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class PublicCvController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $cv = (new OnlineCvService())->publicRaw($slug);
        return Response::html((new CvHtmlRenderer())->render($cv));
    }
}
