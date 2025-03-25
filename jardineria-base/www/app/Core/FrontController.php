<?php

namespace Com\Jardineria\Core;

use Com\Jardineria\Controllers\ErrorController;
use Com\Jardineria\Controllers\ProductoController;
use Steampixel\Route;

class FrontController
{
    public static function main()
    {
        Route::add(
            '/producto',
            function () {
                (new ProductoController())->getProductos();
            },
            'get'
        );

        Route::add(
            '/producto',
            function () {
                (new ProductoController())->insertarProducto();
            },
            'post'
        );

        Route::add(
            '/producto/(.{1,15})',
            function ($codigo) {
                (new ProductoController())->getProducto((string)$codigo);
            },
            'get'
        );

        Route::add(
            '/producto/(.{1,15})',
            function ($codigo) {
                (new ProductoController())->deleteProducto((string)$codigo);
            },
            'delete'
        );

        Route::add(
            '/producto/(.{1,15})',
            function ($codigo) {
                (new ProductoController())->updateProducto((string)$codigo);
            },
            'patch'
        );

        Route::pathNotFound(
            function () {
                (new ErrorController(404))->showError();
            }
        );

        Route::methodNotAllowed(
            function () {
            }
        );
        
        Route::run();
    }
}
