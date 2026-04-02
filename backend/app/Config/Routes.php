<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], static function ($routes) {
    $routes->post('auth/login', 'Auth::login');
    $routes->options('auth/login', static fn () => service('response')->setStatusCode(204));
    $routes->options('auth/me', static fn () => service('response')->setStatusCode(204));
    $routes->options('auth/logout', static fn () => service('response')->setStatusCode(204));
    $routes->options('roles', static fn () => service('response')->setStatusCode(204));
    
    if (ENVIRONMENT === 'testing') {
        $routes->get('test/unhandled-exception', 'TestFailure::triggerUnhandledException');
        $routes->get('test/not-found', 'TestFailure::triggerNotFound');
    }
    
    $routes->group('', ['filter' => 'tokens'], static function ($routes) {
        $routes->get('auth/me', 'Auth::me');
        $routes->post('auth/logout', 'Auth::logout');

        $routes->group('', ['filter' => 'role:Super Admin'], static function ($routes) {
            $routes->get('roles', 'Roles::index');
        });
    });
});
