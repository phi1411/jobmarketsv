<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/scripts/DbStandardizer.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'jobmarket';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

$db = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$standardizer = new JobMarket\Scripts\DbStandardizer($db);
$result = $standardizer->run();
echo "STANDARDIZATION COMPLETE:\n";
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

// Check job-pl-01 and comp-phuclong-lm81
$job = $db->query("
    SELECT j.id, j.title, c.name as comp_name, c.description, c.website, c.contact_phone, c.address as comp_addr
    FROM jobs j
    LEFT JOIN companies c ON j.company_id = c.id
    WHERE j.id = 'job-pl-01'
")->fetch(PDO::FETCH_ASSOC);
echo "\nCheck job-pl-01 company:\n" . json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

$locs = $db->query("SELECT * FROM job_locations WHERE job_id = 'job-pl-01'")->fetchAll(PDO::FETCH_ASSOC);
echo "\nCheck job-pl-01 locations:\n" . json_encode($locs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
