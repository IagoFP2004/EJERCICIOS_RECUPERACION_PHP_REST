<?php

namespace Com\Jardineria\Core;

use Com\Jardineria\Controllers\EmpleadoController;
use Com\Jardineria\Controllers\ErrorController;
use Com\Jardineria\Controllers\ProductoController;
use Steampixel\Route;

class FrontController
{
    public static function main()
    {
        //EJERCICIO JARDINERIA I
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

        //EJERCICIO JARDINERIA II

        Route::add(
            '/empleado',
            function () {
                (new EmpleadoController())->getEmpleados();
            },
            'get'
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
