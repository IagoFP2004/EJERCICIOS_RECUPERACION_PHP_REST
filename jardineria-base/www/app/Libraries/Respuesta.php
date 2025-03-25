<?php

declare(strict_types=1);

namespace Com\Jardineria\Libraries;

class Respuesta
{
    public function __construct(
        private int $code,
        private ?array $data = null
    ){}

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Comprueba si la respuesta tiene cuerpo de datos
     * @return bool true si tiene datos, no en caso de no tenerlo
     */
    public function hasData(): bool
    {
        return !is_null($this->data);
    }

}