<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\UsuarioModel;
use Couchbase\BaseException;

class UsuarioController extends BaseController
{
    public function getUsuarios():void
    {
        $model = new UsuarioModel();
        try {
            $listado = $model->get($_GET);
            $respuesta = new Respuesta(200, $listado);
        }catch(\InvalidArgumentException $e){
            $respuesta = new Respuesta(400,['error'=>$e->getMessage()]);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function getByCodigo(int $codigo):void
    {
        $model = new UsuarioModel();
        $usuario = $model->getByUserCode($codigo);
        if($usuario !== false){
            $respuesta = new Respuesta(200, $usuario);
        }else{
            $respuesta = new Respuesta(400,['error'=>'El codigo de usuario no existe']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

}

