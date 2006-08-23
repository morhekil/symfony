<?php

if (ini_get('zend.ze1_compatibility_mode'))
{
  die("symfony cannot run with zend.ze1_compatibility_mode enabled.\nPlease turn zend.ze1_compatibility_mode to Off in your php.ini.\n");
}

// define some PEAR directory constants
$pear_lib_dir = '@PEAR-DIR@';
$pear_data_dir = '@DATA-DIR@';
define('PAKEFILE_SYMLINK',  false);
define('SYMFONY_VERSION',   '@SYMFONY-VERSION@');

if (is_readable('lib/symfony'))
{
  define('PAKEFILE_LIB_DIR',  'lib/symfony');
  define('PAKEFILE_DATA_DIR', 'data/symfony');
}
else
{
  define('PAKEFILE_LIB_DIR',  '@PEAR-DIR@/symfony/lib');
  define('PAKEFILE_DATA_DIR', '@DATA-DIR@/symfony/data');
}

set_include_path(PAKEFILE_LIB_DIR.'/vendor'.PATH_SEPARATOR.get_include_path());
$pakefile = PAKEFILE_DATA_DIR.'/bin/pakefile.php';

include_once('pake/pakeFunction.php');

// we trap -V before pake
require_once 'pake/pakeGetopt.class.php';
$OPTIONS = array(
  array('--version', '-V', pakeGetopt::NO_ARGUMENT, ''),
  array('--pakefile', '-f', pakeGetopt::OPTIONAL_ARGUMENT, ''),
  array('--tasks', '-T', pakeGetopt::OPTIONAL_ARGUMENT, ''),
);
$opt = new pakeGetopt($OPTIONS);
try
{
  $opt->parse();

  foreach ($opt->get_options() as $opt => $value)
  {
    if ($opt == 'version')
    {
      echo 'symfony version '.SYMFONY_VERSION."\n";
      exit(0);
    }
  }
}
catch (pakeException $e)
{
  print $e->getMessage();
}

$pake = pakeApp::get_instance();
try
{
  $pake->run($pakefile);
}
catch (Exception $e)
{
  echo "ERROR: symfony - ".$e->getMessage()."\n";
}
