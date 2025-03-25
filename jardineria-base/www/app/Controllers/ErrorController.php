<?php

declare(strict_types=1);

namespace Com\Jardineria\Controllers;

use Com\Jardineria\Core\BaseController;
use Com\Jardineria\Libraries\Respuesta;

class ErrorController extends BaseController
{
    public function __construct(
        private int    $code,
        private ?array $data = null
    )
    {
        parent::__construct();
    }

    public function showError()
    {
        $respuesta = new Respuesta($this->code, $this->data);
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }
}