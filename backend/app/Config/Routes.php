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
            "auth/password",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "roles",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "item-categories",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "item-categories/(:num)",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "transaction-types",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "approval-statuses",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "item-units",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "item-units/(:num)",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "item-categories/(:num)/restore",
            static fn() => service("response")->setStatusCode(204),
        );
        $routes->options(
            "item-units/(:num)/restore",
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
            $routes->patch("auth/password", "Auth::changePassword");

            $routes->group(
                "",
                ["filter" => "role:admin,gudang"],
                static function ($routes) {
                    $routes->get("item-categories", "ItemCategories::index");
                    $routes->get("item-categories/(:num)", 'ItemCategories::show/$1');
                    $routes->get("transaction-types", "TransactionTypes::index");
                    $routes->get("approval-statuses", "ApprovalStatuses::index");

                    $routes->get("item-units", "ItemUnits::index");
                    $routes->get("item-units/(:num)", 'ItemUnits::show/$1');

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

                $routes->post("item-categories", "ItemCategories::create");
                $routes->put("item-categories/(:num)", 'ItemCategories::update/$1');
                $routes->delete("item-categories/(:num)", 'ItemCategories::delete/$1');
                $routes->patch("item-categories/(:num)/restore", 'ItemCategories::restore/$1');

                $routes->post("item-units", "ItemUnits::create");
                $routes->put("item-units/(:num)", 'ItemUnits::update/$1');
                $routes->delete("item-units/(:num)", 'ItemUnits::delete/$1');
                $routes->patch("item-units/(:num)/restore", 'ItemUnits::restore/$1');

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
