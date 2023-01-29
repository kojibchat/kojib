<?php
include 'fns/sql/Medoo.php';
use Medoo\Medoo;
class DB
{
    private static $instance = null;
    public static function connect() {
        if (!self::$instance) {
            self::$instance = new Medoo(Registry::load('config')->database);
        }
        return self::$instance;
    }
    private function __clone() {}

    private function __construct() {}
}
?>
