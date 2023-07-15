<?php

namespace JobMarket\Http;

use Exception;

class Kernel
{
    public function __invoke(Request $request): Response
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $routeController) {
            $routes = include BASE_PATH . "/app/Routes/api.php";

            foreach ($routes as $route) {
                $routeController->addRoute(...$route);
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getPathInfo()
        );

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                return new Response([
                    "status" => Response::$statusTexts[Response::HTTP_NOT_FOUND]
                ], Response::HTTP_NOT_FOUND);

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return new Response([
                    "message" => "method not allowed",
                    "allowed methods" => $allowedMethods
                ], Response::HTTP_METHOD_NOT_ALLOWED);

            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $instance = new $handler[0];
                return new Response(call_user_func_array([$instance, $handler[1]], [$request, ...$vars]), Response::HTTP_OK);

            default:
                return new Response();
        }
    }

    public function handler(Request $request): Response
    {
        try {
            $middlewares = include BASE_PATH . "/app/Http/MiddlewareStack.php";

            $firstMiddleware = new $middlewares[0];

            $response = $firstMiddleware($request);

            return $response;
        } catch (Exception $e) {
            return new Response([
                "status" => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
