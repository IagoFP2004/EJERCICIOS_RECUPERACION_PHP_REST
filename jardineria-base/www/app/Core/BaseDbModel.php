<?php

declare(strict_types=1);

namespace Com\Jardineria\Core;

abstract class BaseDbModel
{
    protected \PDO $pdo;

    public function __construct()
    {
        $this->pdo = DBManager::getInstance()->getConnection();
    }
}
