<?php
declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load .env if present (mainly for integration tests)
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    (new Dotenv())->usePutenv()->load($envPath);
}
