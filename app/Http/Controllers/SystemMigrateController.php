<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Facades\Config;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use PDO;
use Throwable;

class SystemMigrateController extends Controller
{
    public function run(Request $request): Response
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

        // If deploy.zip exists, extract it first
        $zipPath = $root . '/deploy.zip';
        if (file_exists($zipPath) && class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($root);
                $zip->close();
                @unlink($zipPath);
            }
        }

        $config = Config::env();

        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config["user"],
                $config["password"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
                ]
            );

            $candidates = [
                $root . '/jobmarket.sql',
                $root . '/public/jobmarket.sql',
                $root . '/storage/jobmarket.sql',
                dirname(__DIR__, 2) . '/jobmarket.sql'
            ];

            $sqlFile = null;
            foreach ($candidates as $c) {
                if (file_exists($c)) {
                    $sqlFile = $c;
                    break;
                }
            }

            if (!$sqlFile) {
                return Response::html("
                    <div style='font-family:sans-serif;max-width:500px;margin:50px auto;padding:20px;border:2px solid #ef4444;border-radius:10px;background:#fef2f2;color:#991b1b;'>
                        <h2>Không tìm thấy tệp jobmarket.sql trên server</h2>
                    </div>
                ");
            }

            $sql = file_get_contents($sqlFile);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("SET NAMES utf8mb4;");
            $pdo->exec($sql);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            @unlink($sqlFile);

            $jobs = (int)$pdo->query("SELECT COUNT(*) FROM `jobs`")->fetchColumn();
            $comps = (int)$pdo->query("SELECT COUNT(*) FROM `companies`")->fetchColumn();
            $users = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();

            return Response::html("
                <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:550px;margin:50px auto;padding:32px;border:1px solid #10b981;border-radius:16px;background:#f0fdf4;box-shadow:0 10px 25px -5px rgba(16,185,129,0.15);text-align:center;'>
                    <div style='font-size:48px;margin-bottom:12px;'>🎉</div>
                    <h1 style='color:#065f46;margin:0 0 8px 0;font-size:24px;'>NẠP DỮ LIỆU THÀNH CÔNG 100%!</h1>
                    <p style='color:#047857;font-size:15px;margin-bottom:24px;'>Toàn bộ dữ liệu từ máy tính đã được đồng bộ chuẩn xác lên hosting:</p>
                    <div style='background:#ffffff;border-radius:12px;padding:16px 24px;margin-bottom:24px;text-align:left;border:1px solid #bbf7d0;'>
                        <div style='display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;'>
                            <span style='color:#475569;'>💼 Tin tuyển dụng việc làm:</span>
                            <strong style='color:#0f172a;'>{$jobs} tin</strong>
                        </div>
                        <div style='display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;'>
                            <span style='color:#475569;'>🏢 Doanh nghiệp / Công ty:</span>
                            <strong style='color:#0f172a;'>{$comps} công ty</strong>
                        </div>
                        <div style='display:flex;justify-content:space-between;padding:8px 0;'>
                            <span style='color:#475569;'>👤 Tài khoản hệ thống:</span>
                            <strong style='color:#0f172a;'>{$users} tài khoản</strong>
                        </div>
                    </div>
                    <a href='/company/dashboard' style='display:inline-block;padding:14px 32px;background:#059669;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:16px;box-shadow:0 4px 12px rgba(5,150,105,0.3);'>
                        👉 Vào Bảng Điều Khiển Ngay
                    </a>
                </div>
            ");
        } catch (Throwable $e) {
            return Response::html("
                <div style='font-family:sans-serif;max-width:550px;margin:50px auto;padding:24px;border:2px solid #ef4444;border-radius:12px;background:#fef2f2;color:#991b1b;'>
                    <h2>Lỗi khi nạp dữ liệu vào Database:</h2>
                    <p>" . htmlspecialchars($e->getMessage()) . "</p>
                </div>
            ");
        }
    }
}
