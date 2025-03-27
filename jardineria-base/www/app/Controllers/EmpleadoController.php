<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\EmpleadoModel;
use Com\Jardineria\Models\OficinaModel;
use Com\Jardineria\Traits\BaseRestController;

class EmpleadoController extends BaseController
{
    use BaseRestController;

    public const CAMPOS =  ['codigo_empleado','nombre','apellido1','apellido2','extension','email','codigo_oficina','codigo_jefe','puesto'];

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

    public function updateEmpleado(int $codigo_empleado):void
    {
        $put  = $this->getParams();
        $errores = $this->checkErrors($put, $codigo_empleado);

        if ($errores ===[]){
            $modelo = new EmpleadoModel();
            $updateData=[];
            foreach (self::CAMPOS as $campo){
                if (isset($put[$campo])){
                    $updateData[$campo]=$put[$campo];
                }
            }
            if ($updateData !== false){
                if ($modelo->patchEmpleado($codigo_empleado,$updateData)){
                    $respuesta = new Respuesta(200,['Mensaje'=>'Usuario actualizado correctamente']);
                }else{
                    $respuesta = new Respuesta(400,['Error'=>'No se pudo actualizar el usuario']);
                }
            }
        }else{
            $respuesta = new Respuesta(400,$errores);
        }

        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function checkErrors(array $data, ?int $codigo_empleado = null): array
    {
        $modelo = new EmpleadoModel();
        $modeloOficina = new OficinaModel();
        $errors = [];
        $editando = !is_null($codigo_empleado);

        if (!$editando){
        if(!$editando && (!isset($data['nombre']) || empty($data['nombre']))){
            $errors['nombre'] = 'Nombre es requerido';
        }else if (strlen($data['nombre']) > 50){
            $errors['nombre'] = 'Nombre debe tener mas de 50 caracteres';
        }

        if(!$editando && (!isset($data['apellido1']) || empty($data['apellido1']))){
            $errors['apellido1'] = 'El primer apellido es requerido';
        }else if (strlen($data['apellido1']) > 50){
            $errors['apellido1'] = 'El primer apellido debe tener mas de 50 caracteres';
        }

        if(isset($data['apellido2'])){
            if (!is_string($data['apellido2'])){
                $errors['apellido2'] = 'Segundo apellido incorrecto';
            }
        }

        if(!$editando && !isset($data['extension']) || empty($data['extension'])){
            $errors['extension'] = 'La extension es requerida';
        }else if (strlen($data['extension']) > 10){
            $errors['extension'] = 'La extension es requerida';
        }

        if (!$editando && !isset($data['email']) || empty($data['email'])){
            $errors['email'] = 'El email es requerido';
        }else if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)===false){
            $errors['email'] = 'El email no es valido';
        }else if(strlen($data['email']) > 100){
            $errores['email'] = 'El email debe tener menos de 100 caracteres';
        }else if ($modelo->getByEmail($data['email']) !== false){
            $errors['email'] = 'El email ya existe';
        }

        if (!$editando && !isset($data['codigo_oficina']) || empty($data['codigo_oficina'])){
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
        }


        return $errors;
    }

}