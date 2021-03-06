<?php

namespace Core;

use http\Exception\UnexpectedValueException;

class View
{
    public static function render($view) {


        $file = dirname(__DIR__) . "/View/$view.php";

        if (is_readable($file)){
            require_once $file;
        }else {
            throw new UnexpectedValueException('File not found!');
        }
    }

    public static function redirect($dest, $statusCode = 301) {
        header('Location: ' . $dest, true, $statusCode);
    }
}