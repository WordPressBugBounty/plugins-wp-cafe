<?php

namespace WpCafe\Container\Exception;

use Exception;
use WpCafe\Container\Not_Found_Exception_Interface;

class Dependency_Has_No_Default_Value_Exception extends Exception implements Not_Found_Exception_Interface {
}
