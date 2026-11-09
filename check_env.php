<?php
require 'bimbelku-backend/vendor/autoload.php';

$app = require 'bimbelku-backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdo = DB::connection()->getPdo();
echo 'DB: ' . DB::connection()->getDatabaseName() . PHP_EOL;
echo 'VERSION: ' . $pdo->query('SELECT VERSION()')->fetchColumn() . PHP_EOL;
echo 'COMMENT: ' . $pdo->query('SELECT @@version_comment')->fetchColumn() . PHP_EOL;
echo 'EXPLICIT: ' . $pdo->query('SELECT @@explicit_defaults_for_timestamp')->fetchColumn() . PHP_EOL;
echo 'SQL_MODE: ' . $pdo->query('SELECT @@sql_mode')->fetchColumn() . PHP_EOL;
?>