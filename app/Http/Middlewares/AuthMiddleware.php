<?php

namespace JobMarket\Http\Middlewares;

use JobMarket\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private string $next = \JobMarket\Http\Kernel::class;
    public function __invoke(\JobMarket\Http\Request $request): Response
    {
        // logic

        $response = call_user_func([new $this->next, "__invoke"], $request);
        return $response;
    }
}
