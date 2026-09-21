<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JobMarket\Http\Kernel as PlatformKernel;
use JobMarket\Http\Request as PlatformRequest;
use JobMarket\Http\Response as PlatformResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->preparePlatformEnvironment();

        $platformRequest = new PlatformRequest(
            $request->query->all(),
            $this->requestPayload($request),
            $request->cookies->all(),
            $_FILES,
            $request->server->all(),
        );

        $platformResponse = (new PlatformKernel())->handler($platformRequest);

        return $this->toFrameworkResponse($platformResponse);
    }

    private function preparePlatformEnvironment(): void
    {
        if (! defined('BASE_PATH')) {
            define('BASE_PATH', dirname(base_path()));
        }

        $aliases = [
            'DB_NAME' => env('DB_NAME', env('DB_DATABASE')),
            'DB_USER' => env('DB_USER', env('DB_USERNAME')),
        ];

        foreach ($aliases as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $value = (string) $value;
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    /** @return array<string, mixed> */
    private function requestPayload(Request $request): array
    {
        $payload = $request->request->all();

        if ($request->isJson()) {
            $json = $request->json()->all();
            if (is_array($json)) {
                $payload = array_replace($payload, $json);
            }
        }

        return $payload;
    }

    private function toFrameworkResponse(PlatformResponse $response): Response
    {
        $headers = $response->getHeaders();
        $status = $response->getStatusCode();

        if ($response->isFile() && $response->getFilePath() !== null) {
            return new BinaryFileResponse(
                $response->getFilePath(),
                $status,
                $headers,
                false,
            );
        }

        $payload = $response->getPayload();
        if (is_string($payload) || $status === 204 || isset($headers['Location'])) {
            return response($status === 204 ? '' : (string) $payload, $status, $headers);
        }

        return response()->json(
            $payload,
            $status,
            $headers,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
    }
}
