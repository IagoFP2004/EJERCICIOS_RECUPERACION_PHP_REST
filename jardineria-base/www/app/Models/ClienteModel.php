<?php

namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;

class ClienteModel extends BaseDbModel
{
    public function getClientes( int $codigo_cliente):bool
    {
        $sql = 'SELECT * FROM cliente WHERE codigo_cliente = :codigo_cliente';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':codigo_cliente', $codigo_cliente, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }
}