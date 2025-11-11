<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API Routes Group
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    
    // Authentication Routes (Public)
    $routes->group('auth', function($routes) {
        $routes->post('register', 'AuthController::register');
        $routes->post('login', 'AuthController::login');
        $routes->get('verify/(:any)', 'AuthController::verifyEmail/$1');
        $routes->post('forgot-password', 'AuthController::forgotPassword');
        $routes->post('reset-password', 'AuthController::resetPassword');
    });
    
    // Test Routes (Development Only)
    $routes->group('test', function($routes) {
        $routes->get('email', 'TestController::testEmail');
        $routes->get('send-email', 'TestController::sendTestEmail');
        $routes->get('info', 'TestController::info');
    });
    
    // Protected Routes (Require Authentication)
    $routes->group('', ['filter' => 'jwt'], function($routes) {
        
        // User Profile Routes
        $routes->group('user', function($routes) {
            $routes->get('profile', 'AuthController::profile');
            $routes->put('profile', 'AuthController::updateProfile');
            $routes->post('logout', 'AuthController::logout');
        });
        
        // Dashboard Routes
        $routes->group('dashboard', function($routes) {
            $routes->get('user', 'DashboardController::userDashboard');
            $routes->get('admin', 'DashboardController::adminDashboard', ['filter' => 'jwt:admin_keuangan,superadmin']);
            $routes->get('superadmin', 'DashboardController::superadminDashboard', ['filter' => 'jwt:superadmin']);
        });
        
        // Service Management Routes
        $routes->group('services', function($routes) {
            $routes->get('/', 'ServiceController::index');
            $routes->get('(:num)', 'ServiceController::show/$1');
            $routes->post('subscribe', 'ServiceController::subscribe');
            $routes->get('my-services', 'ServiceController::myServices');
        });
        
        // Transaction Routes
        $routes->group('transactions', function($routes) {
            $routes->get('/', 'TransactionController::index');
            $routes->get('(:num)', 'TransactionController::show/$1');
            $routes->post('create', 'TransactionController::create');
            $routes->put('(:num)/status', 'TransactionController::updateStatus/$1');
        });
        
        // Waste Collection Routes
        $routes->group('waste-collection', function($routes) {
            $routes->get('/', 'WasteCollectionController::index');
            $routes->get('(:num)', 'WasteCollectionController::show/$1');
            $routes->post('schedule', 'WasteCollectionController::schedule');
            $routes->put('(:num)/complete', 'WasteCollectionController::complete/$1');
        });
        
        // Invoice Routes
        $routes->group('invoices', function($routes) {
            $routes->get('/', 'InvoiceController::index');
            $routes->get('(:num)', 'InvoiceController::show/$1');
            $routes->put('(:num)/pay', 'InvoiceController::markAsPaid/$1');
            $routes->get('(:num)/download', 'InvoiceController::download/$1');
        });
        
        // Document Routes
        $routes->group('documents', function($routes) {
            $routes->get('/', 'DocumentController::index');
            $routes->get('(:num)', 'DocumentController::show/$1');
            $routes->post('upload', 'DocumentController::upload');
            $routes->put('(:num)', 'DocumentController::update/$1');
            $routes->delete('(:num)', 'DocumentController::delete/$1');
        });
        
        // Electronic Manifest Routes
        $routes->group('manifests', function($routes) {
            $routes->get('/', 'ManifestController::index');
            $routes->get('(:num)', 'ManifestController::show/$1');
            $routes->post('create', 'ManifestController::create');
            $routes->put('(:num)/approve', 'ManifestController::approve/$1', ['filter' => 'jwt:superadmin']);
        });
        
        // Admin Routes (Require Admin Role)
        $routes->group('admin', ['filter' => 'jwt:admin_keuangan,superadmin'], function($routes) {
            $routes->get('users', 'AdminController::getUsers');
            $routes->get('users/(:num)', 'AdminController::getUser/$1');
            $routes->put('users/(:num)/status', 'AdminController::updateUserStatus/$1');
            $routes->get('transactions', 'AdminController::getAllTransactions');
            $routes->get('invoices', 'AdminController::getAllInvoices');
        });
        
        // Super Admin Routes (Require Super Admin Role)
        $routes->group('superadmin', ['filter' => 'jwt:superadmin'], function($routes) {
            $routes->get('services', 'SuperAdminController::getServices');
            $routes->post('services', 'SuperAdminController::createService');
            $routes->put('services/(:num)', 'SuperAdminController::updateService/$1');
            $routes->delete('services/(:num)', 'SuperAdminController::deleteService/$1');
            $routes->get('system-stats', 'SuperAdminController::getSystemStats');
        });
    });
});

// Enable CORS for API routes
$routes->options('api/(:any)', function() {
    return service('response')
        ->setHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->setHeader('Access-Control-Allow-Credentials', 'true')
        ->setStatusCode(200);
});

