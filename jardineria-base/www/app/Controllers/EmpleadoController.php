<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\EmpleadoModel;
use Com\Jardineria\Models\OficinaModel;

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

    public function getEmpleado(int $codigo):void
    {
        $modelo = new EmpleadoModel();
        $empleado = $modelo->getByCodigo($codigo);

        if($empleado !== false){
            $respuesta = new Respuesta(200,$empleado);
        }else{
            $respuesta = new Respuesta(404,['Error'=>'No existe el empleado']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function insertEmpleado():void
    {
        $modelo = new EmpleadoModel();
        $errores = $this->checkErrors($_POST);

        if ($errores ===[]){
            $insertado = $modelo->insertEmpleado($_POST);

            if($insertado !== false){
                $respuesta = new Respuesta(200,['Mensaje'=>'Usuario registrado correctamente']);
            }else{
                $respuesta = new Respuesta(400,['Error'=>'No se pudo insertar el usuario']);
            }

        }else{
            $respuesta = new Respuesta(400,$errores);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function deleteEmpleado(int $codigo):void
    {
        $modelo = new EmpleadoModel();

        $borrado = $modelo->delete($codigo);
        if($borrado !== false){
            $respuesta = new Respuesta(200,['Mensaje'=>'Usuario eliminado correctamente']);
        }else{
            $respuesta = new Respuesta(400,['Error'=>'No se pudo eliminar, el usuario no existe']);
        }

        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function checkErrors(array $data, ?int $codigo_empleado = null): array
    {
        $modelo = new EmpleadoModel();
        $modeloOficina = new OficinaModel();
        $errors = [];

        if(!isset($data['nombre']) || empty($data['nombre'])){
            $errors['nombre'] = 'Nombre es requerido';
        }else if (strlen($data['nombre']) > 50){
            $errors['nombre'] = 'Nombre debe tener mas de 50 caracteres';
        }

        if(!isset($data['apellido1']) || empty($data['apellido1'])){
            $errors['apellido1'] = 'El primer apellido es requerido';
        }else if (strlen($data['apellido1']) > 50){
            $errors['apellido1'] = 'El primer apellido debe tener mas de 50 caracteres';
        }

        if(isset($data['apellido2'])){
            if (!is_string($data['apellido2'])){
                $errors['apellido2'] = 'Segundo apellido incorrecto';
            }
        }

        if(!isset($data['extension']) || empty($data['extension'])){
            $errors['extension'] = 'La extension es requerida';
        }else if (strlen($data['extension']) > 10){
            $errors['extension'] = 'La extension es requerida';
        }

        if (!isset($data['email']) || empty($data['email'])){
            $errors['email'] = 'El email es requerido';
        }else if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)===false){
            $errors['email'] = 'El email no es valido';
        }else if(strlen($data['email']) > 100){
            $errores['email'] = 'El email debe tener menos de 100 caracteres';
        }else if ($modelo->getByEmail($data['email']) !== false){
            $errors['email'] = 'El email ya existe';
        }

        if (!isset($data['codigo_oficina']) || empty($data['codigo_oficina'])){
            $errors['codigo_oficina'] = 'El codigo Oficina es requerido';
        }else if(!isset($data['codigo_oficina']) || strlen($data['codigo_oficina']) > 100){
            $errors['codigo_oficina'] = 'El codigo Oficina debe ser un texto no mayor que 100 caracteres';
        }else if ($modeloOficina->getByCodigo($data['codigo_oficina'])===false){
            $errors['codigo_oficina'] = 'El codigo Oficina no es existe';
        }

        if (isset($data['codigo_jefe'])){
            if ($modelo->tieneJefe((int) $data['codigo_jefe'])===false){
                $errors['codigo_jefe'] = 'El codigo jefe no existe';
            }
        }

        if (isset($data['puesto'])){
            if (!is_string($data['puesto']) || strlen($data['puesto'])>50){
                $errors['puesto'] = 'El puesto no debe tener mas de 50 caracteres';
            }
        }

        return $errors;
    }

}