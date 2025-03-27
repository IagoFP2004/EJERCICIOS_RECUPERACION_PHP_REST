<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;

class OficinaModel extends BaseDbModel
{
    public function getByCodigo(string $codigo):bool
    {
        $sql = "SELECT o.codigo_oficina 
                FROM oficina o 
                WHERE o.codigo_oficina = :codigo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':codigo', $codigo, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }
}