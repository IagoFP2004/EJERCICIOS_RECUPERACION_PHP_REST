<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;

class UsuarioModel extends BaseDbModel
{
    public const ORDER_COLUMNS  = ['us.nombre','us.email', 'r.rol'];

    public function get(array $data):array
    {
        if (isset($data['page']) && filter_var($data['page'], FILTER_VALIDATE_INT)!==false) {
            $page = $data['page'];
            if ($page < 1){
                throw new \InvalidArgumentException('El numero de pagina no es valido');
            }
        }else{
            $page = 1;
        }

        if (isset($data['order']) && filter_var($data['order'], FILTER_VALIDATE_INT)!==false) {
            $order = $data['order'];
            if ($order < 1 || $order > count(self::ORDER_COLUMNS)) {
                throw new \InvalidArgumentException('El orden de pagina no es valido');
            }
        }else{
            $order = 1;
        }

        if (isset($data['sentido'])){
            $sentido = $data['sentido'];
            if (!in_array(strtolower($sentido), ['asc', 'desc'])) {
                throw new \InvalidArgumentException('El sentido de pagina no es valido');
            }
        }else{
            $sentido = 'asc';
        }

        $condiciones = [];
        $valores = [];

        if (!empty($data['nombre'])){
            if (!is_string($data['nombre'])){
                throw new \InvalidArgumentException('El nombre es incorrecto');
            }else{
                $condiciones[] = "us.nombre LIKE :nombre";
                $valores['nombre'] = "%".$data['nombre']."%";
            }
        }

        if (!empty($data['email'])){
            if(filter_var($data['email'], FILTER_VALIDATE_EMAIL)===false){
                throw new \InvalidArgumentException('El email es incorrecto');
            }else{
                $condiciones[] = "us.email LIKE :email";
                $valores['email'] = "%".$data['email']."%";
            }
        }

        if (!empty($data['id_rol'])){
            if(filter_var($data['id_rol'], FILTER_VALIDATE_INT)===false){
                throw new \InvalidArgumentException('El id rol es incorrecto');
            }else{
                $condiciones[] = "us.id_rol = :id_rol";
                $valores['id_rol'] = $data['id_rol'];
            }
        }

        if (!empty($data['fecha_login_min'])){
            if (!is_string($data['fecha_login_min'])){
                throw new \InvalidArgumentException('La fecha login minima es incorrecta');
            }else{
                $condiciones[] = "us.last_date >= :fecha_login_min";
                $valores['fecha_login_min'] = $data['fecha_login_min'];
            }
        }

        if (!empty($data['fecha_login_max'])){
            if (!is_string($data['fecha_login_max'])){
                throw new \InvalidArgumentException('La fecha login maxima es incorrecta');
            }else{
                $condiciones[] = "us.last_date <= :fecha_login_max";
                $valores['fecha_login_max'] = $data['fecha_login_max'];
            }
        }

        $sql = "SELECT us.id_usuario, us.email,  us.nombre, us.last_date, us.idioma, us.baja, us.id_rol, r.rol  
                FROM usuario_sistema us 
                LEFT JOIN rol r ON r.id_rol = us.id_rol ";
        if(!empty($condiciones)){
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }
        $sql .= " ORDER BY ".self::ORDER_COLUMNS[$order-1]." ".$sentido;
        $sql.= " LIMIT ".($page-1)*$_ENV['limite.pagina'].",".$_ENV['limite.pagina'];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function getByUserCode(int $codigo):array|false
    {
        $sql ="SELECT  us.id_usuario, us.email,  us.nombre, us.last_date, us.idioma, us.baja, us.id_rol, r.rol 
               FROM usuario_sistema us 
               LEFT JOIN rol r ON r.id_rol = us.id_rol
               WHERE us.id_usuario = :codigo ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo'=>$codigo]);
        return $stmt->fetch();
    }

    public function insert(array $data):int|false
    {
        $sql = 'INSERT INTO `usuario_sistema`(`id_rol`, `email`, `pass`, `nombre`, `idioma`, `baja`) 
                VALUES (:id_rol,:email,:pass,:nombre,:idioma,:baja)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_rol'=>$data['id_rol'],
            'email'=>$data['email'],
            'pass'=>password_hash($data['password'],PASSWORD_DEFAULT),
            'nombre'=>$data['nombre'],
            'idioma'=>$data['idioma'],
            'baja'=>$data['baja']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $codigo):bool
    {
        $sql = "DELETE FROM usuario_sistema WHERE id_usuario = :codigo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo'=>$codigo]);
        return $stmt->rowCount() === 1;
    }

    public function patch(int $codigo,array $data):bool
    {
        if(!empty($data)){
            $sql = 'UPDATE usuario_sistema SET';
            $campos = [];
            foreach ($data as $column => $value) {
                $campos[] = "`$column` = :$column";
            }
            $sql .= implode(', ', $campos);
            $sql .= " WHERE id_usuario = :codigo";
            $data['codigo'] = $codigo;
            return $stmt = $this->pdo->prepare($sql)->execute($data);
        }else{
            return false;
        }
    }

    public function getByEmail(string $email):array | false
    {
        $sql = "SELECT * FROM usuario_sistema WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email'=>$email]);
        return $stmt->fetch();
    }

    public function changePasswordUser(string $email,string $new_password):bool
    {
        $sql = "UPDATE usuario_sistema SET pass = :new_password WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':new_password',password_hash($new_password,PASSWORD_DEFAULT));
        $stmt->bindParam(':email',$email);
        return $stmt->execute();
    }
}