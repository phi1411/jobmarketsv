<?php

namespace JobMarket\Http\Middlewares;

use JobMarket\Http\Request;
use JobMarket\Http\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request): Response;
}