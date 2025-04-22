<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\ClienteModel;
use Com\Jardineria\Models\PedidoModel;
use DateTime;

class PedidoController extends BaseController
{
    public function getPedidos():void
    {
        try {
            $modelo = new PedidoModel();
            $pedido = $modelo->listado($_GET);
            $respuesta = new Respuesta(200,$pedido);
        }catch (\InvalidArgumentException $e){
            $respuesta = new Respuesta(400,['Error'=>$e->getMessage()]);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function getPedidoByCode(int $codigo_pedido):void
    {
        $modelo = new PedidoModel();
        $producto = $modelo ->getProducto($codigo_pedido);
        if($producto !==false){
            $respuesta = new Respuesta(200,$producto);
        }else{
            $respuesta = new Respuesta(400,['Error'=>'El pedido no existe']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function insertarPedido():void
    {
        $modelo = new PedidoModel();
        $errores = $this->checkErrors($_POST);
        if ($errores === []){
            if ($modelo->getProducto((int)$_POST['codigo_pedido']) !== false){
                $respuesta  = new Respuesta(400,['Error'=>'El codigo ya existe']);
            }else{
                $insert=$modelo->insertar($_POST);
                if($insert !== false){
                    $respuesta = new Respuesta(200,['Mensaje'=>$_ENV['base.url'].'/pedido/'.$_POST['codigo_pedido']]);
                }else{
                    $respuesta = new Respuesta(400,['Error'=>'Ha ocurrido un error']);
                }
            }
        }else{
            $respuesta = new Respuesta(400,$errores);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function deletePedido(int $codigo_pedido):void
    {
        $modelo = new PedidoModel();
        $borrar = $modelo->delete($codigo_pedido);

        if($borrar){
            $respuesta = new Respuesta(200,['Mensaje'=>'Pedido eliminado']);
        }else{
            $respuesta = new Respuesta(400,['Error'=>'Ha ocurrido un error']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function checkErrors(array $data, ?int $codigo_pedido=null):array
    {
        $clienteModel = new ClienteModel();
        $errors = [];
        $editando = !is_null($codigo_pedido);

        if(!$editando || (!empty($data['codigo_pedido']))){
            if(!$editando && empty($data['codigo_pedido'])) {
                $errors['codigo_pedido'] = 'Codigo pedido es requerido';
            }else if (filter_var($data['codigo_pedido'],FILTER_VALIDATE_INT) === false) {
                $errors['codigo_pedido'] = "codigo pedido no es un debe ser  numero valido";
            }
        }

        if (!$editando || (!empty($data['fecha_pedido']))) {
            if (!$editando && empty($data['fecha_pedido'])) {
                $errors['fecha_pedido'] = 'Fecha pedido es requerido';
            } else {
                $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_pedido']);
                if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_pedido']) {
                    $errors['fecha_pedido'] = 'Fecha pedido debe tener un formato válido (YYYY-MM-DD)';
                }
            }
        }

        if (!$editando || (!empty($data['fecha_esperada']))) {
            if (!$editando && empty($data['fecha_esperada'])) {
                $errors['fecha_esperada'] = 'Fecha esperada es requerido';
            } else {
                $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_esperada']);
                if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_esperada']) {
                    $errors['fecha_esperada'] = 'Fecha pedido debe tener un formato válido (YYYY-MM-DD)';
                }
            }
        }

       if (isset($data['fecha_entrega'])) {
           $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_entrega']);
           if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_entrega']) {
               $errors['fecha_entrega'] = 'Fecha entrega debe tener un formato válido (YYYY-MM-DD)';
           }
       }

       if (isset($data['comentarios'])) {
           if (!is_string($data['comentarios'])) {
               $errors['comentarios'] = 'Comentarios debe ser un string';
           }
       }

        if(!$editando || (!empty($data['codigo_cliente']))){
            if(!$editando && empty($data['codigo_cliente'])) {
                $errors['codigo_cliente'] = 'Codigo cliente es requerido';
            }else if ($clienteModel->getClientes((int)$data['codigo_cliente']) === false) {
                $errors['codigo_cliente'] = "el cliente no existe";
            }
        }

        return $errors;
    }

}