<?php

namespace WHMCS\Module\Addon\OpenproviderSsl\Admin;

use WHMCS\Module\Addon\OpenproviderSsl\Admin\Controller;

class AdminDispatcher
{

    public function dispatch($action, $parameters)
    {
        $controller = new Controller($parameters);

        if (is_callable(array($controller, $action))) {
            return $controller->$action();
        }

        return '<p>Invalid action requested. Please go back and try again.</p>';
    }
}
