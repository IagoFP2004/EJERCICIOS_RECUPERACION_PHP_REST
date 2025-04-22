<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;
use DateTime;

class PedidoModel extends BaseDbModel
{

    public const ORDER_COLUMNS = ['p.fecha_pedido', 'p.estado', 'c.nombre_cliente'];

    public function listado(array $data):array
    {
        if(isset($data['page'])&& filter_var($data['page'], FILTER_VALIDATE_INT)!==false) {
            $page = $data['page'];
            if ($page < 1) {
                throw new \InvalidArgumentException('La pagina no puede ser menor que 1');
            }
        }else{
            $page = 1;
        }

        if(isset($data['order'])&& filter_var($data['order'], FILTER_VALIDATE_INT)!==false) {
            $order = $data['order'];
            if ($order <1 || $order > count(self::ORDER_COLUMNS)) {
                throw new \InvalidArgumentException('La orden no puede ser menor que 1 ni mayor a 3');
            }
        }else{
            $order = 1;
        }

        if (isset($data['sentido'])){
            if (!in_array(strtolower($data['sentido']), ['asc','desc'])) {
                throw new \InvalidArgumentException('Sentido no valido, solo se permite asc o desc');
            }
        }else{
            $sentido = 'asc';
        }

        $condiciones = [];
        $valores = [];

        if(!empty($data['nombre_cliente'])){
            if (!is_string($data['nombre_cliente'])){
                throw new \InvalidArgumentException('Nombre cliente no valido');
            }else{
                $condiciones[] = "c.nombre_cliente LIKE :nombre_cliente";
                $valores["nombre_cliente"] = "%".$data['nombre_cliente']."%";
            }
        }

        if (!empty($data['comentarios'])){
            if (!is_string($data['comentarios'])){
                throw new \InvalidArgumentException('Comentarios no validos');
            }else{
                $condiciones[] = "p.comentarios LIKE :comentarios";
                $valores["comentarios"] = "%".$data['comentarios']."%";
            }
        }

        if (!empty($data['estado'])){
            if (!in_array(strtolower($data['estado']), ['entregado','pendiente','rechazado'])){
                throw new \InvalidArgumentException('Estado no valido , solo puede ser entregado, pendiente, rechazado');
            }else{
                $condiciones[] = "p.estado = :estado";
                $valores["estado"] = $data['estado'];
            }
        }

        if (!empty($data['fecha_pedido_min'])) {
            if (!is_string($data['fecha_pedido_min']) || !DateTime::createFromFormat('d/m/Y', $data['fecha_pedido_min'])) {
                throw new \InvalidArgumentException('Fecha pedido min no válida. Formato esperado: dd/mm/yyyy');
            } else {
                $fecha = DateTime::createFromFormat('d/m/Y', $data['fecha_pedido_min']);
                $condiciones[] = "p.fecha_pedido >= :fecha_pedido_min";
                $valores["fecha_pedido_min"] = $fecha->format('Y-m-d');
            }
        }

        if (!empty($data['fecha_pedido_max'])) {
            if (!is_string($data['fecha_pedido_max']) || !DateTime::createFromFormat('d/m/Y', $data['fecha_pedido_max'])) {
                throw new \InvalidArgumentException('Fecha pedido min no válida. Formato esperado: dd/mm/yyyy');
            } else {
                $fecha = DateTime::createFromFormat('d/m/Y', $data['fecha_pedido_max']);
                $condiciones[] = "p.fecha_pedido >= :fecha_pedido_max";
                $valores["fecha_pedido_max"] = $fecha->format('Y-m-d');
            }
        }


        $sql = "SELECT p.codigo_pedido, p.fecha_pedido, p.fecha_esperada, p.fecha_entrega, p.estado, p.comentarios, p.codigo_cliente, c.nombre_cliente, c.nombre_contacto,
                c.apellido_contacto, c.telefono, c.fax, c.linea_direccion1, c.linea_direccion2, c.ciudad, c.region, c.pais, c.codigo_postal, c.codigo_empleado_rep_ventas, c.limite_credito
                FROM pedido p
                LEFT JOIN detalle_pedido dp ON dp.codigo_pedido = p.codigo_pedido
                LEFT JOIN cliente c ON c.codigo_cliente = p.codigo_cliente ";
        if (!empty($condiciones)){
            $sql .= " WHERE ".implode(" AND ", $condiciones);
        }
        $sql.= ' GROUP BY p.codigo_pedido';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function getProducto(int $codigo_pedido):array|false
    {
        $sql = "SELECT p.codigo_pedido, p.fecha_pedido, p.fecha_esperada, p.fecha_entrega, p.estado, p.comentarios, p.codigo_cliente, c.nombre_cliente, c.nombre_contacto,
                c.apellido_contacto, c.telefono, c.fax, c.linea_direccion1, c.linea_direccion2, c.ciudad, c.region, c.pais, c.codigo_postal, c.codigo_empleado_rep_ventas, c.limite_credito
                FROM pedido p
                LEFT JOIN detalle_pedido dp ON dp.codigo_pedido = p.codigo_pedido
                LEFT JOIN cliente c ON c.codigo_cliente = p.codigo_cliente
                WHERE p.codigo_pedido = :codigo_pedido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["codigo_pedido"=>$codigo_pedido]);
        return $stmt->fetch() ?: false;
    }

    public function insertar(array $data):int | false
    {
        $sql =" INSERT INTO pedido (`codigo_pedido`, `fecha_pedido`, `fecha_esperada`, `fecha_entrega`, `estado`, `comentarios`, `codigo_cliente`) 
                VALUES (:codigo_pedido,:fecha_pedido,:fecha_esperada,:fecha_entrega,:estado,:comentarios,:codigo_cliente)";

        if(!isset($data['fecha_entrega'])){
            $data['fecha_entrega'] = null;
        }
        if(!isset($data['estado'])){
            $data['estado'] = 'Pendiente';
        }
        if(!isset($data['comentarios'])){
            $data['comentarios'] = null;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();

    }

    public function delete(int $codigo_pedido):bool
    {
        $sql = "DELETE FROM pedido WHERE codigo_pedido = :codigo_pedido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["codigo_pedido"=>$codigo_pedido]);
        return $stmt->rowCount() === 1;
    }


}