<?php

namespace Com\Jardineria\Core;

use Ahc\Jwt\JWT;
use Ahc\Jwt\JWTException;
use Com\Jardineria\Controllers\EmpleadoController;
use Com\Jardineria\Controllers\ErrorController;
use Com\Jardineria\Controllers\PedidoController;
use Com\Jardineria\Controllers\ProductoController;
use Com\Jardineria\Controllers\UsuarioController;
use Com\Jardineria\Helpers\JwtTool;
use Com\Jardineria\Models\UsuarioModel;
use Steampixel\Route;

class FrontController
{
    private static ?array $jwtData = null;
    private static array $permisos = [];
    public static function main()
    {
        if (JwtTool::requestHasToken()){
            try {
                $bearer = JwtTool::getBearerToken();
                $jwt = new JWT($_ENV['secreto']);
                self::$jwtData = $jwt->decode($bearer);
                self::$permisos = UsuarioController::getPermisos(self::$jwtData['user_type']);
            }catch (JWTException $e){
                $controller = new ErrorController(403,['mensaje'=>$e->getMessage()]);
                $controller->showError();
                die();
            }
        }else{
            self::$permisos = UsuarioController::getPermisos();
        }

        //EJERCICIO JARDINERIA I
        Route::add(
            '/producto',
            function () {
                if (str_contains(self::$permisos['producto'],'r')){
                    (new ProductoController())->getProductos();
                }else{
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/producto',
            function () {
                if (str_contains(self::$permisos['producto'],'w')) {
                    (new ProductoController())->insertarProducto();
                }else{
                    http_response_code(403);
                }
            },
            'post'
        );

        Route::add(
            '/producto/(.{1,15})',
            function ($codigo) {
                if (str_contains(self::$permisos['producto'],'r')) {
                    (new ProductoController())->getProducto((string)$codigo);
                }else{
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/producto/(.{1,15})',
            function ($codigo) {
                if (str_contains(self::$permisos['producto'],'d')) {
                    (new ProductoController())->deleteProducto((string)$codigo);
                }else{
                    http_response_code(403);
                }
            },
            'delete'
        );

        Route::add(
            '/producto/(.{1,15})',
            function ($codigo) {
                if (str_contains(self::$permisos['producto'],'w')) {
                    (new ProductoController())->updateProducto((string)$codigo);
                }else{
                    http_response_code(403);
                }
            },
            'patch'
        );

        //EJERCICIO JARDINERIA II

        Route::add(
            '/empleado',
            function () {
                if (str_contains(self::$permisos['empleado'],'r')) {
                    (new EmpleadoController())->getEmpleados();
                }else{
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/empleado/([0-9]+)',
            function ($codigo) {
                if (str_contains(self::$permisos['empleado'],'r')) {
                    (new EmpleadoController())->getEmpleado((int)$codigo);
                }else{
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/empleado',
            function () {
                if (str_contains(self::$permisos['empleado'],'w')) {
                    (new EmpleadoController())->insertEmpleado();
                }else{
                    http_response_code(403);
                }
            },
            'post'
        );

        Route::add(
            '/empleado/([0-9]+)',
            function ($codigo) {
                if (str_contains(self::$permisos['empleado'],'d')) {
                    (new EmpleadoController())->deleteEmpleado((int)$codigo);
                }else{
                    http_response_code(403);
                }
            },
            'delete'
        );

        Route::add(
            '/empleado/([0-9]+)',
            function ($codigo) {
                if (str_contains(self::$permisos['empleado'],'w')) {
                    (new EmpleadoController())->updateEmpleado((int)$codigo);
                }else{
                    http_response_code(403);
                }
            },
            'patch'
        );

        //EJERCICIO JARDINERIA III

        Route::add(
            '/pedido',
            function () {
                (new PedidoController())->getPedidos();
            },
            'get'
        );

        Route::add(
            '/pedido/([0-9]+)',
            function ($codigo_pedido) {
                (new PedidoController())->getPedidoByCode((int)$codigo_pedido);
            },
            'get'
        );

        Route::add(
            '/pedido',
            function () {
                (new PedidoController())->insertarPedido();
            },
            'post'
        );

        Route::add(
            '/pedido/([0-9]+)',
            function ($codigo_pedido) {
                (new PedidoController())->deletePedido((int)$codigo_pedido);
            },
            'delete'
        );

        //EJERCICIO JARDINERIA IV

        Route::add(
            '/login',
            function () {
                (new UsuarioController())->login();
            },
            'post'
        );

        Route::add(
            '/change-password',
            function () {
                if(self::$jwtData!==null){
                    (new UsuarioController())->changePassword((string) self::$jwtData['email']);
                }else{
                    http_response_code(403);
                }
            },
            'put'
        );

        Route::add(
            '/usuario',
            function () {
                (new UsuarioController())->getUsuarios();
            },
            'get'
        );

        Route::add(
            '/usuario/([0-9]+)',
            function ($codigo) {
                (new UsuarioController())->getByCodigo((int) $codigo);
            },
            'get'
        );

        Route::add(
            '/usuario',
            function () {
                (new UsuarioController())->insertarUsuario();
            },
            'post'
        );

        Route::add(
            '/usuario/([0-9]+)',
            function ($codigo) {
                (new UsuarioController())->deleteUsuario((int) $codigo);
            },
            'delete'
        );

        Route::add(
            '/usuario/([0-9]+)',
            function ($codigo) {
                (new UsuarioController())->updateUsuario((int) $codigo);
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
