<?php
declare(strict_types=1);
namespace Com\Jardineria\Controllers;

use Ahc\Jwt\JWT;
use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;
use Com\Jardineria\Models\RolModel;
use Com\Jardineria\Models\UsuarioModel;
use Com\Jardineria\Traits\BaseRestController;
use Couchbase\BaseException;

class UsuarioController extends BaseController
{
    public const CAMPOS = ['id_usuario','email','nombre','last_date','idioma','baja','id_rol','rol'];

    public const ROL_ADMINISTRADOR = 1;
    public const ROL_JARDINERO = 2;
    public const ROL_FACTURACION = 3;

    use BaseRestController;
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

    public function insertarUsuario():void
    {
        $errores = $this->checkErrors($_POST);
        if ($errores ===[]){
            $modelo = new UsuarioModel();
            $insert = $modelo->insert($_POST);
            $respuesta = new Respuesta(200, ['Exito'=>$_ENV['base.url'].'/usuario/'.$insert]);
        }else{
            $respuesta = new Respuesta(400, $errores);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function deleteUsuario(int $codigo):void
    {
        $model = new UsuarioModel();
        $borrado = $model->delete($codigo);
        if ($borrado !== false){
            $respuesta = new Respuesta(200, ['Exito'=>'El codigo de usuario '.$codigo.' ha sido eliminado']);
        }else{
            $respuesta = new Respuesta(400,['error'=>'El codigo de usuario '.$codigo.' no existe']);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function updateUsuario(int $codigo):void
    {
        $modelo = new UsuarioModel();
        if ($modelo->getByUserCode($codigo) === false){
            $respuesta = new Respuesta(404,['error'=>'El codigo de usuario '.$codigo.' no existe']);
        }else{
            $put = $this->getParams();
            $errores = $this->checkErrors($put, $codigo);
            if ($errores ===[]){
                $modelo = new UsuarioModel();
                $updateData = [];
                foreach(self::CAMPOS as $campo){
                    if(isset($put[$campo])){
                        $updateData[$campo] = $put[$campo];
                    }
                }
                if ($updateData !== []){
                    if ($modelo->patch($codigo,$updateData) !== false){
                        $respuesta = new Respuesta(200,['Mensaje'=>'Registro actualizado correctamente']);
                    }else{
                        $respuesta = new Respuesta(400,['error'=>'No se pudo actualizar']);
                    }
                }else{
                    $respuesta = new Respuesta(400,['error'=>'No se han enviado campos para actualizar']);
                }
            }else{
                $respuesta = new Respuesta(400, $errores);
            }
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function login():void
    {
        if (empty($_POST['email']) || empty($_POST['password'])){
            $respuesta = new Respuesta(400,['error'=>'Es necesario enviar email y contraseña']);
        }else{
            $modelo = new UsuarioModel();
            $login = $modelo->getByEmail($_POST['email']);
            if ($login !== false){
                if (password_verify($_POST['password'],$login['pass'])){
                    $payload =[
                        'name'=>$login['nombre'],
                        'user_type'=>$login['id_rol'],
                        'email'=>$login['email'],
                    ];
                    $jwt = new JWT($_ENV['secreto'],'HS256',1800);
                    $token = $jwt->encode($payload);
                    $respuesta = new Respuesta(200,['Token'=>$token]);
                }else{
                    $respuesta = new Respuesta(403,['error'=>'La contraseña es incorrecta']);
                }
            }else{
                $respuesta = new Respuesta(403,['error'=>'El email no existe']);
            }
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public static function getPermisos(int $idRol=-1):array
    {
        $permisos = [
            'productos'=>'',
            'pedidos'=>'',
            'empleado'=>'',
        ];
        return match ($idRol){
            self::ROL_ADMINISTRADOR=>array_replace($permisos,['productos'=>'rwd','pedidos'=>'rwd','empleado'=>'rwd']),
            self::ROL_JARDINERO=>array_replace($permisos,['productos'=>'rwd','pedidos'=>'','empleado'=>'']),
            self::ROL_FACTURACION=>array_replace($permisos,['productos'=>'','pedidos'=>'rwd','empleado'=>'rw']),
            default=>$permisos
        };
    }

    public function changePassword(string $email):void
    {
        $put = $this->getParams();
        $errores = $this->checkChangePassword($put);

        if ($errores ===[]){
            $model = new UsuarioModel();
            $login = $model->getByEmail($email);

            if ($login !== false && password_verify($put['old_password'],$login['pass'])){
                if ($model->changePasswordUser($email,$put['new_password'])){
                    $respuesta = new Respuesta(200,['Exito'=>'La contraseña ha sido actualizada']);
                }else{
                    $respuesta = new Respuesta(500,['Error'=>'No se pudo actualizar la contraseña']);
                }
            }else{
                $respuesta = new Respuesta(403,['mensaje'=>'La contraseña es incorrecta']);
            }
        }else{
            $respuesta = new Respuesta(403, $errores);
        }
        $this->view->show('json.view.php',['respuesta'=>$respuesta]);
    }

    public function checkErrors(array $data, ?int $codigo=null):array
    {
        $errors = [];
        $editando = !is_null($codigo);
        $rolModel = new RolModel();
        $modelo = new UsuarioModel();

        if(!$editando || (!empty($data['nombre']))){
            if(!$editando && empty($data['nombre'])) {
                $errors['nombre']='El nombre es requerido';
            }else if (!is_string($data['nombre'])) {
                $errors['nombre']='El nombre debe ser una cadena de texto';
            }else if (strlen($data['nombre']) > 255) {
                $errors['nombre']='El nombre no debe tener mas de 255 caracteres';
            }
        }

        if (!$editando || (!empty($data['id_rol']))){
            if(!$editando && empty($data['id_rol'])) {
                $errors['id_rol']='El rol es requerido';
            }else if(filter_var($data['id_rol'],FILTER_VALIDATE_INT) === false) {
                $errors['id_rol']='El id rol debe ser un numero';
            }else if($rolModel->getIdRol((int)$data['id_rol']) === false){
                $errors['id_rol']='El rol no existe';
            }
        }

        if (!$editando || (!empty($data['email']))){
            if(!$editando && empty($data['email'])) {
                $errors['email']='El email es requerido';
            }else if (filter_var($data['email'],FILTER_VALIDATE_EMAIL)===false) {
                $errors['email']='El email no es un email valido';
            }else if (strlen($data['email']) > 255) {
                $errors['email']='El email no debe tener mas de 255 caracteres';
            }else if ($modelo->getByEmail($data['email']) !== false){
                $errors['email']='El email ya existe';
            }
        }

        if (!$editando || (!empty($data['password']))){
            if(!$editando && empty($data['password'])) {
                $errors['password']='El password es requerido';
            }else if(!is_string($data['password'])) {
                $errors['password']='El password debe ser una cadena de texto';
            }else if (strlen($data['password']) > 8) {
                $errors['password']='El password no debe tener mas de 8 caracteres';
            }
        }

        if (!$editando || (!empty($data['idioma']))){
            if(!$editando && empty($data['idioma'])) {
                $errors['idioma']='El idioma es requerido';
            }else if(!in_array($data['idioma'],['es','gl','en'])){
                $errors['idioma']='El idioma no existe, solo puede ser en,gl,es';
            }
        }

        if (!$editando || (!empty($data['baja']))){
            if(!$editando && empty($data['baja'])) {
                $errors['baja']='El baja es requerido';
            }else if (filter_var($data['baja'],FILTER_VALIDATE_INT)===false) {
                $errors['baja']='La baja debe ser un numero';
            }else if (!in_array($data['baja'],['1','0'])) {
                $errors['baja']='La baja solo puede ser 1 o 0';
            }
        }

        return $errors;
    }

    public function checkChangePassword(array $data):array
    {
        $errors = [];

        if (empty($data['old_password'])) {
            $errors['old_password']='Se necesita la anterior contraseña';
        }

        if (empty($data['new_password'])) {
            $errors['new_password'] = "Indique la contraseña para enviar";
        }else if (!preg_match("/^(?=.*[a-z])(?=.*\d).{8,}$/",$data['new_password'])) {
            $errors['new_password'] = "La nueva contraseña debe tener una longitud >= 8 y contener al menos una letra y un número";
        }

        return $errors;
    }

}

