<?php

namespace JobMarket\Http\Middlewares;

use JobMarket\Facades\JWT;
use JobMarket\Facades\Session;
use JobMarket\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private string $next = \JobMarket\Http\Kernel::class;
    public function __invoke(\JobMarket\Http\Request $request): Response
    {
        // logic

        if (!($request->getPathInfo() === "/login" || $request->getPathInfo() === "/register" || $request->getPathInfo() === "/")) {
            if (Session::token() === null) {
                return new Response([
                    "message" => Response::$statusTexts[Response::HTTP_UNAUTHORIZED]
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        $response = call_user_func([new $this->next, "__invoke"], $request);
        return $response;
    }
}
