<?php

namespace JobMarket\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use JobMarket\Exceptions\AppException;
use JobMarket\Facades\Config;
use JobMarket\Support\Logger;
use Throwable;

class Kernel
{
    public function __invoke(Request $request): Response
    {
        // Handle CORS Pre-flight OPTIONS request
        if ($request->getMethod() === "OPTIONS") {
            return new Response([], Response::HTTP_NO_CONTENT);
        }

        // 1. If client requests HTML (browser navigation), check Web Routes first
        if ($request->wantsHtml()) {
            $webDispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $routeCollector) {
                $webRoutes = include BASE_PATH . "/app/Routes/web.php";
                foreach ($webRoutes as $route) {
                    $routeCollector->addRoute(...$route);
                }
            });

            $webRouteInfo = $webDispatcher->dispatch(
                $request->getMethod(),
                $request->getPathInfo()
            );

            if ($webRouteInfo[0] === Dispatcher::FOUND) {
                $handler = $webRouteInfo[1];
                $vars = $webRouteInfo[2];
                $instance = new $handler[0]();

                $result = call_user_func_array([$instance, $handler[1]], [$request, ...$vars]);
                if ($result instanceof Response) {
                    return $result;
                }
                return Response::html((string)$result);
            }
        }

        // 2. Dispatch to API Routes
        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $routeCollector) {
            $routes = include BASE_PATH . "/app/Routes/api.php";

            foreach ($routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getPathInfo()
        );

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return Response::error(
                    "Đường dẫn yêu cầu không tồn tại trên hệ thống",
                    Response::HTTP_NOT_FOUND
                );

            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return Response::error(
                    "Phương thức HTTP {$request->getMethod()} không được hỗ trợ cho đường dẫn này",
                    Response::HTTP_METHOD_NOT_ALLOWED,
                    ["allowed_methods" => $allowedMethods]
                );

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $instance = new $handler[0]();

                $result = call_user_func_array([$instance, $handler[1]], [$request, ...$vars]);

                if ($result instanceof Response) {
                    return $result;
                }

                return Response::success($result);

            default:
                return Response::error("Không thể xử lý yêu cầu", Response::HTTP_BAD_REQUEST);
        }
    }

    public function handler(Request $request): Response
    {
        try {
            $middlewares = include BASE_PATH . "/app/Http/MiddlewareStack.php";
            $firstMiddleware = new $middlewares[0]();

            return $firstMiddleware($request);
        } catch (AppException $e) {
            // Business exceptions (Validation, Auth, Not Found, Forbidden)
            return Response::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrors()
            );
        } catch (Throwable $e) {
            // Unhandled system errors
            Logger::error($e->getMessage(), [
                "file"  => $e->getFile(),
                "line"  => $e->getLine(),
                "trace" => $e->getTraceAsString(),
                "path"  => $request->getPathInfo(),
                "method"=> $request->getMethod()
            ]);

            $message = Config::isDebug()
                ? $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
                : "Đã có lỗi hệ thống xảy ra. Vui lòng liên hệ quản trị viên.";

            return Response::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
