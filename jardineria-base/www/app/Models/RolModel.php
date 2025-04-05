<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;

use Com\Jardineria\Core\BaseDbModel;

class RolModel extends BaseDbModel
{
    public function getIdRol(int $idRol):bool
    {
        $sql = 'SELECT r.id_rol FROM rol r WHERE r.id_rol = :idRol';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idRol' => $idRol]);
        return $stmt->rowCount() === 1;
    }
}