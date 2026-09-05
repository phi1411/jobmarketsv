<?php

namespace JobMarket\Support;

class View
{
    private static string $viewsDir = BASE_PATH . "/app/Views";

    /**
     * Render a view file without layout
     */
    public static function render(string $view, array $data = []): string
    {
        $viewFile = self::$viewsDir . "/" . str_replace(".", "/", $view) . ".php";
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Không tìm thấy tệp giao diện: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        return ob_get_clean() ?: "";
    }

    /**
     * Render a view file wrapped inside a layout
     */
    public static function renderWithLayout(string $view, array $data = [], string $layout = "layouts/main"): string
    {
        $content = self::render($view, $data);
        $layoutData = array_merge($data, ["content" => $content]);

        return self::render($layout, $layoutData);
    }
}
