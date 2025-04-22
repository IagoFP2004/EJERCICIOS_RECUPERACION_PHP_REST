<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\PedidoModel;

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
}