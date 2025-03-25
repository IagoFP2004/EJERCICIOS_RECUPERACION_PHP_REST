<?php
declare(strict_types=1);
namespace Com\Jardineria\Models;
use Com\Jardineria\Core\BaseDbModel;
use PDO;

class GamaModel extends BaseDbModel
{
 public function getGama(string $gama): bool
 {
     $sql = "SELECT gp.gama FROM gama_producto gp WHERE gp.gama = :gama;";
     $stmt = $this->pdo->prepare($sql);
     $stmt->bindParam('gama',$gama);
     $stmt->execute();
     return $stmt->rowCount() === 1;
 }
}