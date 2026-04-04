<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get("/", "Home::index");

$routes->group(
    "api/v1",
    ["namespace" => "App\Controllers\Api\V1", "filter" => "cors"],
    static function ($routes) {
        $routes->post("auth/login", "Auth::login");
        $routes->options(
            "auth/login",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "auth/me",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "auth/logout",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "roles",
            static fn() => service("response")->setStatusCode(204),
        );

        if (ENVIRONMENT === "testing") {
            $routes->get(
                "test/unhandled-exception",
                "TestFailure::triggerUnhandledException",
            );
            $routes->get("test/not-found", "TestFailure::triggerNotFound");
        }

        $routes->group("", ["filter" => "tokens"], static function ($routes) {
            $routes->get("auth/me", "Auth::me");
            $routes->post("auth/logout", "Auth::logout");

            $routes->group(
                "",
                ["filter" => "role:admin,gudang"],
                static function ($routes) {
                    $routes->get("items", "Items::index");
                    $routes->options(
                        "items",
                        static fn() => service("response")->setStatusCode(204),
                    );
                    $routes->post("items", "Items::create");
                    $routes->get("items/(:num)", 'Items::show/$1');
                    $routes->options(
                        "items/(:num)",
                        static fn() => service("response")->setStatusCode(204),
                    );
                    $routes->put("items/(:num)", 'Items::update/$1');

                    $routes->get(
                        "stock-transactions",
                        "StockTransactions::index",
                    );
                    $routes->options(
                        "stock-transactions",
                        static fn() => service("response")->setStatusCode(204),
                    );
                    $routes->post(
                        "stock-transactions",
                        "StockTransactions::create",
                    );
                    $routes->get(
                        "stock-transactions/(:num)",
                        'StockTransactions::show/$1',
                    );
                    $routes->options(
                        "stock-transactions/(:num)",
                        static fn() => service("response")->setStatusCode(204),
                    );
                    $routes->get(
                        "stock-transactions/(:num)/details",
                        'StockTransactions::details/$1',
                    );
                    $routes->options(
                        "stock-transactions/(:num)/details",
                        static fn() => service("response")->setStatusCode(204),
                    );
                    $routes->post(
                        "stock-transactions/(:num)/submit-revision",
                        'StockTransactions::submitRevision/$1',
                    );
                    $routes->options(
                        "stock-transactions/(:num)/submit-revision",
                        static fn() => service("response")->setStatusCode(204),
                    );
                },
            );

            $routes->group("", ["filter" => "role:admin"], static function (
                $routes,
            ) {
                $routes->get("roles", "Roles::index");

                $routes->post(
                    "stock-transactions/(:num)/approve",
                    'StockTransactions::approve/$1',
                );
                $routes->options(
                    "stock-transactions/(:num)/approve",
                    static fn() => service("response")->setStatusCode(204),
                );
                $routes->post(
                    "stock-transactions/(:num)/reject",
                    'StockTransactions::reject/$1',
                );
                $routes->options(
                    "stock-transactions/(:num)/reject",
                    static fn() => service("response")->setStatusCode(204),
                );

                // User management endpoints
                $routes->get("users", "Users::index");
                $routes->options(
                    "users",
                    static fn() => service("response")->setStatusCode(204),
                );
                $routes->post("users", "Users::create");
                $routes->get("users/(:num)", 'Users::show/$1');
                $routes->options(
                    "users/(:num)",
                    static fn() => service("response")->setStatusCode(204),
                );
                $routes->put("users/(:num)", 'Users::update/$1');
                $routes->patch("users/(:num)/activate", 'Users::activate/$1');
                $routes->options(
                    "users/(:num)/activate",
                    static fn() => service("response")->setStatusCode(204),
                );
                $routes->patch(
                    "users/(:num)/deactivate",
                    'Users::deactivate/$1',
                );
                $routes->options(
                    "users/(:num)/deactivate",
                    static fn() => service("response")->setStatusCode(204),
                );
                $routes->patch(
                    "users/(:num)/password",
                    'Users::changePassword/$1',
                );
                $routes->options(
                    "users/(:num)/password",
                    static fn() => service("response")->setStatusCode(204),
                );
                $routes->delete("users/(:num)", 'Users::delete/$1');
                $routes->delete("items/(:num)", 'Items::delete/$1');
            });
        });
    },
);
