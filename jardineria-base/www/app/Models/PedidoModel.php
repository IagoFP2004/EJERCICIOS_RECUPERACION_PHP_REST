<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;

class PedidoModel extends BaseDbModel
{
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



        $sql = "SELECT p.codigo_pedido, p.fecha_pedido, p.fecha_esperada, p.fecha_entrega, p.estado, p.comentarios, p.codigo_cliente, c.nombre_cliente, c.nombre_contacto,
                c.apellido_contacto, c.telefono, c.fax, c.linea_direccion1, c.linea_direccion2, c.ciudad, c.region, c.pais, c.codigo_postal, c.codigo_empleado_rep_ventas, c.limite_credito
                FROM pedido p
                LEFT JOIN detalle_pedido dp ON dp.codigo_pedido = p.codigo_pedido
                LEFT JOIN cliente c ON c.codigo_cliente = p.codigo_cliente 
                GROUP BY p.codigo_pedido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}