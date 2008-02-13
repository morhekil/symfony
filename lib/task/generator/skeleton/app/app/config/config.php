<?php

// include project configuration
include(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

// symfony bootstraping
require_once($sf_symfony_lib_dir.'/util/sfCore.class.php');
sfCore::bootstrap($sf_symfony_lib_dir);

// insert your own autoloading callables here

if (sfConfig::get('sf_debug'))
{
  spl_autoload_register(array(sfAutoload::getInstance(), 'autoloadAgain'));
}