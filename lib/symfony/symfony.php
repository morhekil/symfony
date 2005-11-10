<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004, 2005 Sean Kerr.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Pre-initialization script.
 *
 * @package    symfony
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */

/**
 * Handles autoloading of classes that have been specified in autoload.yml and myautoload.yml.
 *
 * @param string A class name.
 *
 * @return void
 */
function __autoload($class)
{
  // this static variable is generated by the $config file below
  static $classes;

  if (!isset($classes))
  {
    try
    {
      // include the list of autoload classes
      $config = sfConfigCache::checkConfig(SF_APP_CONFIG_DIR_NAME.'/autoload.yml');
    }
    catch (sfException $e)
    {
      $e->printStackTrace();
    }
    catch (Exception $e)
    {
      // unknown exception
      $e = new sfException($e->getMessage());

      $e->printStackTrace();
    }

    require_once($config);
  }

  if (!isset($classes[$class]))
  {
    // unspecified class
    $error = 'Autoloading of class "%s" failed';
    $error = sprintf($error, $class);
    $e = new sfAutoloadException($error);

    $e->printStackTrace();
  }

  // class exists, let's include it
  require_once($classes[$class]);
}

try
{
  ini_set('unserialize_callback_func', '__autoload');

  // symfony version information
  require_once('symfony/version.php');

  if (!defined('SF_IN_BOOTSTRAP') || !SF_IN_BOOTSTRAP)
  {
    // YAML support
    require_once('spyc/spyc.php');
    require_once('symfony/util/sfYaml.class.php');

    // cache support
    require_once('symfony/cache/sfCache.class.php');
    require_once('symfony/cache/sfFileCache.class.php');

    // config support
    require_once('symfony/config/sfConfigCache.class.php');
    require_once('symfony/config/sfConfigHandler.class.php');
    require_once('symfony/config/sfYamlConfigHandler.class.php');
    require_once('symfony/config/sfAutoloadConfigHandler.class.php');
    require_once('symfony/config/sfRootConfigHandler.class.php');

    // basic exception classes
    require_once('symfony/exception/sfException.class.php');
    require_once('symfony/exception/sfAutoloadException.class.php');
    require_once('symfony/exception/sfCacheException.class.php');
    require_once('symfony/exception/sfConfigurationException.class.php');
    require_once('symfony/exception/sfParseException.class.php');

    // utils
    require_once('symfony/util/sfParameterHolder.class.php');
    require_once('symfony/util/sfToolkit.class.php');

    // create bootstrap file for next time
    if (!SF_DEBUG && !SF_TEST)
    {
      sfConfigCache::checkConfig(SF_APP_CONFIG_DIR_NAME.'/bootstrap_compile.yml');
    }
  }

  // set exception format
  sfException::setFormat(isset($_SERVER['HTTP_HOST']) ? 'html' : 'plain');

  if (SF_DEBUG)
  {
    // clear our config and module cache
    sfConfigCache::clear();
  }

  // load base settings
  sfConfigCache::import(SF_APP_CONFIG_DIR_NAME.'/logging.yml');
  sfConfigCache::import(SF_APP_CONFIG_DIR_NAME.'/php.yml');
  sfConfigCache::import(SF_APP_CONFIG_DIR_NAME.'/settings.yml');
  sfConfigCache::import(SF_APP_CONFIG_DIR_NAME.'/app.yml');

  // error settings
  ini_set('display_errors', SF_DEBUG ? 'on' : 'off');
  error_reporting(SF_ERROR_REPORTING);

  // compress output
  ob_start(SF_COMPRESSED ? 'ob_gzhandler' : '');

/*
  if (SF_LOGGING_ACTIVE)
  {
    set_error_handler(array('sfLogger', 'errorHandler'));
  }
*/

  // required core classes for the framework
  // we create a temp var to avoid substitution during compilation
  if (!SF_TEST)
  {
    $core_classes = SF_APP_CONFIG_DIR_NAME.'/core_compile.yml';
    sfConfigCache::import($core_classes);
  }

  if (SF_ROUTING)
  {
    $routing_config = SF_APP_CONFIG_DIR_NAME.'/routing.yml';
    sfConfigCache::import($routing_config);
  }
}
catch (sfException $e)
{
  $e->printStackTrace();
}
catch (Exception $e)
{
  // unknown exception
  $e = new sfException($e->getMessage());

  $e->printStackTrace();
}

?>