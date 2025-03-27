<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\EmpleadoModel;

class EmpleadoController extends BaseController
{
    public function getEmpleados():void
    {
        $modelo = new EmpleadoModel();
        try {
            $listado = $modelo->getEmpleados($_GET);
            $respuesta = new Respuesta(200,$listado);
        }catch (\InvalidArgumentException $e){
            $respuesta = new Respuesta(400,['Error'=>$e->getMessage()]);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }
}