<?php

  namespace Com\Jardineria\Core;

use Com\Jardineria\Libraries\Mensaje;

abstract class BaseController
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View(get_class($this));
    }

}
