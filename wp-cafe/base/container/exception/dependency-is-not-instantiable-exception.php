<?php

namespace WpCafe\Container\Exception;

use Exception;
use WpCafe\Container\Container_Exception_Interface;

class Dependency_Is_Not_Instantiable_Exception extends Exception implements Container_Exception_Interface {
}
