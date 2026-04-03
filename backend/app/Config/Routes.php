<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'cors'], static function ($routes) {
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

        $routes->group('', ['filter' => 'role:admin'], static function ($routes) {
            $routes->get('roles', 'Roles::index');
            
            // User management endpoints
            $routes->get('users', 'Users::index');
            $routes->options('users', static fn () => service('response')->setStatusCode(204));
            $routes->post('users', 'Users::create');
            $routes->get('users/(:num)', 'Users::show/$1');
            $routes->options('users/(:num)', static fn () => service('response')->setStatusCode(204));
            $routes->put('users/(:num)', 'Users::update/$1');
            $routes->patch('users/(:num)/activate', 'Users::activate/$1');
            $routes->options('users/(:num)/activate', static fn () => service('response')->setStatusCode(204));
            $routes->patch('users/(:num)/deactivate', 'Users::deactivate/$1');
            $routes->options('users/(:num)/deactivate', static fn () => service('response')->setStatusCode(204));
            $routes->patch('users/(:num)/password', 'Users::changePassword/$1');
            $routes->options('users/(:num)/password', static fn () => service('response')->setStatusCode(204));
            $routes->delete('users/(:num)', 'Users::delete/$1');
        });
    });
});
