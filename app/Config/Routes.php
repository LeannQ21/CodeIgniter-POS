<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Login must be accessible while logged out.
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');

// Routes that require a logged-in user.
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Pages::home');
    $routes->get('about', 'Pages::about');

    $routes->get('customers', 'Customers::index');
    $routes->get('customers/new', 'Customers::new');
    $routes->post('customers/create', 'Customers::create');
    $routes->get('customers/edit/(:num)', 'Customers::edit/$1');
    $routes->post('customers/update/(:num)', 'Customers::update/$1');
    $routes->post('customers/delete/(:num)', 'Customers::delete/$1');

    $routes->get('users', 'Users::index');
    $routes->get('users/new', 'Users::new');
    $routes->post('users/create', 'Users::create');
    $routes->get('users/edit/(:num)', 'Users::edit/$1');
    $routes->post('users/update/(:num)', 'Users::update/$1');
    $routes->post('users/delete/(:num)', 'Users::delete/$1');
    $routes->post('users/avatar/(:num)', 'Users::uploadAvatar/$1');

    $routes->get('products', 'Products::index');
    $routes->get('products/new', 'Products::new');
    $routes->post('products/create', 'Products::create');
    $routes->get('products/edit/(:num)', 'Products::edit/$1');
    $routes->post('products/update/(:num)', 'Products::update/$1');
    $routes->post('products/delete/(:num)', 'Products::delete/$1');
    $routes->post('products/image/(:num)', 'Products::uploadImage/$1');

    $routes->get('sales', 'Sales::index');
    $routes->get('sales/new', 'Sales::new');
    $routes->post('sales/create', 'Sales::create');

    $routes->post('logout', 'Auth::logout');
});