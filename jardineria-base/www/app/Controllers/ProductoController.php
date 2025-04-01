<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Traits\BaseRestController;
use ErrorException;
use Com\Jardineria\Models\ProductoModel;

class ProductoController extends BaseController
{
    use BaseRestController;

    private const CAMPOS = ['codigo_producto', 'nombre','gama','dimensiones','proveedor','descripcion','cantidad_en_stock','precio_venta','precio_proveedor'];
    public function getProductos():void
    {
        $modelo = new ProductoModel();
        try{
            $listado = $modelo->get($_GET);
            $respuesta = new Respuesta(200,$listado);
        }catch (\InvalidArgumentException $e){
            $respuesta = new Respuesta(400,['error'=>$e->getMessage()]);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function insertarProducto():void
    {
        $modelo = new ProductoModel();
        if ($modelo->getByCodigo($_POST['codigo_producto'])) {
            $respuesta = new Respuesta(400,['error'=>'Ya existe un producto con este codigo']);
        }else{
            $errores = $this->checkErrors($_POST);
            if ($errores === []){
                foreach ($_POST as $key => $value) {
                    if (trim($value) === '') {
                        $_POST[$key] = null;
                    }
                }
                $modelo = new ProductoModel();
                $insertar = $modelo->insert($_POST);
                if ($insertar){
                    $respuesta = new Respuesta(201,['mensaje'=>'Registro insertado correctamente']);
                }else{
                    $respuesta = new Respuesta(400,['error'=>'No se pudo insertar el registro']);
                }
            }else{
                $respuesta = new Respuesta(400,$errores);
            }
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function getProducto(string $codigo):void
    {
        $modelo = new ProductoModel();
        $producto = $modelo->getByCodigo($codigo);

        if($producto != false){
            $respuesta = new Respuesta(200,$producto);
        }else{
            $respuesta = new Respuesta(404,['error'=>'El producto no existe']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function deleteProducto(string $codigo):void
    {
        $modelo = new ProductoModel();
        $producto = $modelo->delete($codigo);

        if($producto){
            $respuesta = new Respuesta(200,['Mensaje'=>'El producto se ha eliminado']);
        }else{
            $respuesta = new Respuesta(404,['error'=>'El producto no existe']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function updateProducto( string $codigo):void
    {
        $model = new ProductoModel();
        if($model->getByCodigo($codigo) == false){
            $respuesta = new Respuesta(404);
        }else{
            $put = $this->getParams();
            $errores = $this->checkErrors($put,$codigo);
            if ($errores === []){
                $model = new ProductoModel();
                $updateData=[];
                foreach (self::CAMPOS as $campo){
                    if (isset($put[$campo])){
                        $updateData[$campo] = $put[$campo];
                    }
                }
                if($updateData !==[]){
                    if($model->patchProducto($codigo,$updateData)){
                        $respuesta = new Respuesta(200,['Mensaje'=>'Registro actualizado correctamente']);
                    }else{
                        $respuesta = new Respuesta(400,['error'=>'No se pudo actualizar el registro']);
                    }
                }else{
                    $respuesta = new Respuesta(400, ['mensaje' => 'No se han enviado datos para actualizar']);
                }
            }else{
                $respuesta = new Respuesta(400,$errores);
            }
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function checkErrors(array $data, ?string $codigo=null): array
    {
        $errores = [];
        $editando = !is_null($codigo);
        //var_dump($data);

        if (!isset($data['codigo_producto']) && !$editando ) {
            $errores['codigo_producto'] = 'El codigo es obligatorio';
        } elseif (empty($data['codigo_producto'])) {
            $errores['codigo_producto'] = 'El codigo no puede estar vacio';
        } elseif (!preg_match('/^.{1,15}$/', $data['codigo_producto'])) {
            $errores['codigo_producto'] = 'El codigo no es valido';
        }

        if (!isset($data['nombre']) && !$editando) {
            $errores['nombre'] = 'El nombre es obligatorio';
        }elseif (empty($data['nombre'])) {
            $errores['nombre'] = 'El nombre no puede estar vacio';
        }elseif (!is_string($data['nombre'])) {
            $errores['nombre'] = 'El nombre debe ser un texto';
        }

        if (!$editando && !isset($data['gama'])) {
            $errores['gama'] = 'El gama es obligatorio';
        } elseif (!in_array($data['gama'], ['Aromáticas', 'Frutales', 'Herbaceas', 'Herramientas', 'Ornamentales'])) {
            $errores['gama'] = 'El gama solo puede ser Aromáticas, Frutales, Herbaceas, Herramientas, Ornamentales';
        }

        if (isset($data['dimensiones']) ) {
            if (!is_string($data['dimensiones']) || strlen($data['dimensiones']) > 25) {
                $errores['dimensiones'] = 'El dimensiones debe ser una cadena de texto no mayor de 25 caracteres';
            }
        }

        if (isset($data['proveedor'])) {
            if (filter_var($data['proveedor'],FILTER_VALIDATE_URL) === false) {
                $errores['proveedor'] = 'Proveedor no es una URL valida';
            }
            if(strlen($data['proveedor']) > 50){
                $errores['proveedor']= 'Proveedor no puede tener mas de 50 caracteres';
            }
        }

        if (isset($data['descripcion'])) {
            if (!is_string($data['descripcion'])) {
                $errores['descripcion'] = 'Descripcion no válida';
            }
        }

        if (!$editando && (!isset($data['cantidad_en_stock']) || !is_numeric($data['cantidad_en_stock']) || $data['cantidad_en_stock'] <= 0)) {
            $errores['cantidad_en_stock'] = 'El stock debe ser un número mayor que 0';
        }

        if (!$editando && (!isset($data['precio_venta']) || !preg_match('/^\d{1,13}(\.\d{1,2})?$/', $data['precio_venta']))) {
            $errores['precio_venta'] = 'El precio debe ser un número con máximo 15 dígitos y 2 decimales';
        }

        if (isset($data['precio_proveedor'])) {
            if (!preg_match('/^\d{1,13}(\.\d{1,2})?$/', $data['precio_proveedor'])) {
                $errores['precio_proveedor'] = 'El precio debe ser un número con máximo 15 dígitos y 2 decimales';
            }
        }

        return $errores;
    }

}