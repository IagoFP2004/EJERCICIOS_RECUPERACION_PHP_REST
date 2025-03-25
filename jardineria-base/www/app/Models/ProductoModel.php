<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;
use Com\Jardineria\Core\BaseDbModel;
use InvalidArgumentException;
class ProductoModel extends \Com\Jardineria\Core\BaseDbModel
{
    public const ORDER_COLUMNS = ['p.nombre', 'p.gama', 'p.cantidad_en_stock', 'p.precio_venta', 'p.dimensiones'];
    public function get(array $data) : array
    {
        if(isset($data['page'])&& filter_var($data['page'], FILTER_VALIDATE_INT)) {
            $page = $data['page'];
            if ($page < 1) {
                throw new InvalidArgumentException('La pagina no puede ser menor que 1');
            }
        }else{
            $page = 1;
        }

        if(isset($data['order'])&& filter_var($data['order'], FILTER_VALIDATE_INT)) {
            $order = $data['order'];
            if ($order < 1 || $order > count(self::ORDER_COLUMNS)) {
                throw new InvalidArgumentException('La orden no puede ser menor que 1 ni mayor que el numero de campos');
            }
        }else{
            $order = 1;
        }

        if(isset($data['sentido'])) {
            if(in_array(strtolower($data['sentido']), ['asc','desc'],true)) {
                $sentido = $data['sentido'];
            }else{
                throw new InvalidArgumentException('El sentido solo puede ser asc o desc');
            }
        }else{
            $sentido = 'asc';
        }

        $condiciones = [];
        $condicionesHaving = [];
        $valores = [];

        if (!empty($data['nombre'])) {
            if(!is_string($data['nombre'])) {
                throw new InvalidArgumentException('El nombre debe ser una cadena de texto');
            }else{
                $condiciones[] = 'p.nombre LIKE :nombre';
                $valores['nombre'] = '%'.$data['nombre'].'%';
            }
        }

        if (!empty($data['descripcion'])) {
            if(!is_string($data['descripcion'])) {
                throw new InvalidArgumentException('La descripcion debe ser una cadena de texto');
            }else{
                $condiciones[] = 'p.descripcion LIKE :descripcion';
                $valores['descripcion'] = '%'.$data['descripcion'].'%';
            }
        }

        if (!empty($data['gama'])) {
            if(!is_string($data['gama'])) {
                throw new InvalidArgumentException('El gama debe ser una cadena de texto');
            }else{
                $condiciones[] = 'p.gama = :gama';
                $valores['gama'] = $data['gama'];
            }
        }

        if (!empty($data['min_pv'])) {
            if(filter_var($data['min_pv'], FILTER_VALIDATE_FLOAT)== false) {
                throw new InvalidArgumentException('El precio de venta debe ser un numero');
            }else{
                $condiciones[] = 'p.precio_venta >= :min_pv';
                $valores['min_pv'] = $data['min_pv'];
            }
        }

        if (!empty($data['max_pv'])) {
            if(filter_var($data['max_pv'], FILTER_VALIDATE_FLOAT)== false){
                throw new InvalidArgumentException('El precio de venta debe ser un numero');
            }else{
                $condiciones[] = 'p.precio_venta <= :max_pv';
                $valores['max_pv'] = $data['max_pv'];
            }
        }

        if(!empty($data['min_uds_vendidas'])) {
            if(!filter_var($data['min_uds_vendidas'], FILTER_VALIDATE_INT)=== false || $data['min_uds_vendidas'] < 0) {
                throw new InvalidArgumentException('El precio de venta debe ser un numero >=0');
            }else{
                $condicionesHaving[] = 'ventas_ultimos_6m >= :min_uds_vendidas';
                $valores['min_uds_vendidas'] = $data['min_uds_vendidas'];
            }
        }

        if(!empty($data['max_uds_vendidas'])) {
            if(filter_var($data['max_uds_vendidas'], FILTER_VALIDATE_INT)=== false || $data['max_uds_vendidas'] < 0) {
                throw new InvalidArgumentException('El precio de venta debe ser un numero >=0');
            }else{
                $condicionesHaving[] = 'ventas_ultimos_6m <= :max_uds_vendidas';
                $valores['max_uds_vendidas'] = $data['max_uds_vendidas'];
            }
        }

        $sql = "SELECT p.*,IFNULL(SUM(dp.cantidad), 0)  as ventas_ultimos_6m
                FROM producto p 
                LEFT JOIN detalle_pedido dp ON dp.codigo_producto = p.codigo_producto AND DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 MONTH)";
        if(!empty($condiciones)){
            $sql .= " WHERE ".implode(" AND ", $condiciones);
        }
        $sql .= " GROUP BY p.codigo_producto ";
        if(!empty($condicionesHaving)){
            $sql .= " HAVING ".implode(" AND ", $condicionesHaving);
        }
        $sql.= " ORDER BY ".self::ORDER_COLUMNS[$order -1]." ".$sentido;
        $sql.= " LIMIT ". ($page-1)*$_ENV['limite.pagina'].",".$_ENV['limite.pagina'];
        $stmt = $this->pdo->prepare($sql);
        //var_dump($sql);
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function getByCodigo(string $codigo):array|false
    {
      $sql = "SELECT p.*,IFNULL(SUM(dp.cantidad), 0)  as ventas_ultimos_6m
              FROM producto p 
              LEFT JOIN detalle_pedido dp ON dp.codigo_producto = p.codigo_producto AND DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 MONTH) WHERE p.codigo_producto = :codigo";
        $sql .= " GROUP BY p.codigo_producto ";
        $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['codigo' => $codigo]);
      return $stmt->fetch();
    }

    public function delete(string $codigo):bool
    {
        $sql = "DELETE FROM producto WHERE codigo_producto = :codigo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        return  $stmt->rowCount()===1;
    }

    public function insert(array $data):bool
    {
        $sql = 'INSERT INTO `producto`(`codigo_producto`, `nombre`, `gama`, `dimensiones`, `proveedor`, `descripcion`, `cantidad_en_stock`, `precio_venta`, `precio_proveedor`) 
        VALUES (:codigo_producto,:nombre,:gama,:dimensiones,:proveedor,:descripcion,:cantidad_en_stock,:precio_venta,:precio_proveedor)';

        if (!isset($data['descripcion'])) {
            $data['descripcion'] = NULL;
        }
        if(!isset($data['dimensiones'])){
            $data['dimensiones'] = NULL;
        }
        if(!isset($data['proveedor'])){
            $data['proveedor'] = NULL;
        }
        if (!isset($data['precio_proveedor'])) {
            $data['precio_proveedor'] = NULL;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function patchProducto(string $codigo,array $data):bool
    {
        if(!empty($data)){
            $sql = "Update producto SET ";
            $campos = [];
            foreach ($data as $campo => $value) {
                $campos[]= "$campo = :$campo";
            }
            $sql .= implode(',',$campos);
            $sql .= " WHERE codigo_producto = :codigo";
            $data['codigo'] = $codigo;
            return $stmt = $this->pdo->prepare($sql)->execute($data);
        }else{
            return false;
        }
    }

}