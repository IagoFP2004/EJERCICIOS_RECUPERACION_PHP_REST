<?php
declare (strict_types=1);
namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;
use InvalidArgumentException;

class EmpleadoModel extends BaseDbModel
{
    public const CAMPOS_ORDER = ['e.nombre, e.apellido1, e.apellido2','e.apellido1, e.apellido2, e.nombre','e.puesto', 'e.extension'];


    public function getEmpleados(array $data):array
    {
        if(isset($data['page'])&& filter_var($data['page'], FILTER_VALIDATE_INT)) {
            $page = (int) $data['page'];
            if ($page < 1) {
                throw new InvalidArgumentException('La pagina no puede ser menor que 1');
            }
        }else{
            $page = 1;
        }

        if (isset($data['order'])&& filter_var($data['order'], FILTER_VALIDATE_INT)) {
            $order = (int) $data['order'];
            if ($order < 1 || $order > count(self::CAMPOS_ORDER)) {
                throw new InvalidArgumentException('El orden es incorrecto');
            }
        }else{
            $order = 1;
        }

        if (isset($data['sentido'])){
            if(in_array(strtolower($data['sentido']),['asc','desc'])){
                $sentido = $data['sentido'];
            }else{
                throw new InvalidArgumentException('El sentido es incorrecto solo puede ser ASC o DESC');
            }
        }else{
            $sentido = 'asc';
        }

        $condiciones = [];
        $valores = [];

        if(!empty($data['nombre_completo'])){
            if (!is_string($data['nombre_completo'])) {
                throw new InvalidArgumentException('El nombre completo es incorrecto ');
            }else{
                $condiciones[]= "CONCAT(e.nombre,' ',e.apellido1,' ',e.apellido2) LIKE :nombre_completo";
                $valores['nombre_completo'] = '%'.$data['nombre_completo'].'%';
            }
        }

        if(!empty($data['puesto'])){
            if (!is_string($data['puesto'])) {
                throw new InvalidArgumentException('El puesto es incorrecto');
            }else{
                $condiciones[] = 'e.puesto = :puesto';
                $valores['puesto'] = $data['puesto'];
            }
        }

        if(!empty($data['cod_oficina'])){
            if (!is_string($data['cod_oficina'])) {
                throw new InvalidArgumentException('El codigo de oficina es incorrecto');
            }else{
                $condiciones[] = 'e.cod_oficina = :cod_oficina';
                $valores['cod_oficina'] = $data['cod_oficina'];
            }
        }

        if(!empty($data['email'])){
            if (!is_string($data['email']) || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('El email no es valido');
            }else{
                $condiciones[] = 'e.email = :email';
                $valores['email'] = $data['email'];
            }
        }

        $sql="SELECT e.*, e2.nombre as nombre_jefe, e2.apellido1 as apellido1_jefe, e2.apellido2 as apellido2_jefe 
              FROM  empleado e
              LEFT JOIN empleado e2 ON e2.codigo_empleado  = e.codigo_jefe";
        if (!empty($condiciones)) {
            $sql.= " WHERE ".implode(' AND ',$condiciones);
        }
        $sql.= " ORDER BY " . str_replace(',', " $sentido,", self::CAMPOS_ORDER[$order-1]) . " $sentido";
        $sql.= " LIMIT ".($page-1)*$_ENV['limite.pagina'].",".$_ENV['limite.pagina'];
        //var_dump($sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function getByCodigo(int $codigo):array | false
    {
        $sql = "SELECT e.*, e2.nombre as nombre_jefe, e2.apellido1 as apellido1_jefe, e2.apellido2 as apellido2_jefe 
                FROM  empleado e
                LEFT JOIN empleado e2 ON e2.codigo_empleado  = e.codigo_jefe WHERE e.codigo_empleado = :codigo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch();
    }

    public function insertEmpleado(array $data):bool
    {
        $sql ="INSERT INTO empleado (`nombre`, `apellido1`, `apellido2`, `extension`, `email`, `codigo_oficina`, `codigo_jefe`, `puesto`) 
                VALUES (:nombre,:apellido1,:apellido2,:extension,:email,:codigo_oficina,:codigo_jefe,:puesto)";

        if (!isset($data['apellido2'])){
            $data['apellido2'] = NULL;
        }
        if (!isset($data['codigo_jefe'])){
            $data['codigo_jefe'] = NULL;
        }
        if(!isset($data['puesto'])){
            $data['puesto'] = NULL;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $codigo_empleado):bool
    {
        $sql="DELETE FROM empleado WHERE codigo_empleado = :codigo_empleado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo_empleado' => $codigo_empleado]);
        return  $stmt->rowCount()===1;
    }

    public function patchEmpleado(int $codigo, array $data):bool
    {
        if (!empty($data)){
            $sql = "UPDATE empleado SET ";
            $campos = [];
            foreach ($data as $key => $value) {
                $campos[] = "$key = :$key";
            }
            $sql .= implode(', ', $campos);
            $sql .= " WHERE codigo_empleado = :codigo_empleado";
            $data['codigo_empleado'] = $codigo;
            return  $stmt = $this->pdo->prepare($sql)->execute($data);
        }else{
            return false;
        }
    }

    public function getByEmail(string $email):array | false
    {
        $sql = "SELECT * FROM empleado WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function tieneJefe(int $codigo_empleado):bool
    {
        $sql = "SELECT e.codigo_empleado 
            FROM empleado e 
            WHERE e.codigo_empleado = :codigo_empleado 
            AND e.codigo_jefe IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo_empleado' => $codigo_empleado]);
        return $stmt->rowCount() === 1;
    }
}