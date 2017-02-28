<?php

define('DEBUG', true);
define('ROOT', dirname(__FILE__));

define('LOGIN', "localhost:8080/GroupProject/index.html");

require_once (ROOT."/backend/database.php");
require_once (ROOT."/backend/authentication.php");

// Named autoloader
function autoload_class($class){
    include_once(ROOT. "/backend/" . $class . ".class.php");
}
spl_autoload_register('autoload_class');