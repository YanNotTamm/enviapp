<?php
require '../vendor/autoload.php';

$app = \Config\Services::codeigniter();
$app->initialize();

$migrate = \Config\Services::migrations();

try {
    $migrate->latest();
    echo "✓ Migrations completed successfully!";
} catch (\Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage();
}
