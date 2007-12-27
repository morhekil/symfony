<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfPhpConfigHandler allows you to override php.ini configuration at runtime.
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPhpConfigHandler extends sfYamlConfigHandler
{
  /**
   * Executes this configuration handler
   *
   * @param array An array of absolute filesystem path to a configuration file
   *
   * @return string Data to be written to a cache file
   *
   * @throws <b>sfConfigurationException</b> If a requested configuration file does not exist or is not readable
   * @throws <b>sfParseException</b> If a requested configuration file is improperly formatted
   * @throws <b>sfInitializationException</b> If a php.yml key check fails
   */
  public function execute($configFiles)
  {
    $this->initialize();

    // parse the yaml
    $config = $this->parseYamls($configFiles);

    // init our data array
    $data = array();

    // get all php.ini configuration
    $configs = ini_get_all();

    // set some php.ini keys
    if (isset($config['set']))
    {
      foreach ($config['set'] as $key => $value)
      {
        $key = strtolower($key);

        // key exists?
        if (!array_key_exists($key, $configs))
        {
          throw new sfParseException(sprintf('Configuration file "%s" specifies key "%s" which is not a php.ini directive.', $configFiles[0], $key));
        }

        // key is overridable?
        if ($configs[$key]['access'] != 7)
        {
          throw new sfParseException(sprintf('Configuration file "%s" specifies key "%s" which cannot be overrided.', $configFiles[0], $key));
        }

        // escape value
        $value = str_replace("'", "\\'", $value);

        $data[] = sprintf("ini_set('%s', '%s');", $key, $value);
      }
    }

    // check some php.ini settings
    if (isset($config['check']))
    {
      foreach ($config['check'] as $key => $value)
      {
        $key = strtolower($key);

        // key exists?
        if (!array_key_exists($key, $configs))
        {
          throw new sfParseException(sprintf('Configuration file "%s" specifies key "%s" which is not a php.ini directive.', $configFiles[0], $key));
        }

        if (ini_get($key) != $value)
        {
          throw new sfInitializationException(sprintf('Configuration file "%s" specifies that php.ini "%s" key must be set to "%s". The current value is "%s" (%s).', $configFiles[0], $key, var_export($value, true), var_export(ini_get($key), true), $this->get_ini_path()));
        }
      }
    }

    // warn about some php.ini settings
    if (isset($config['warn']))
    {
      foreach ($config['warn'] as $key => $value)
      {
        $key = strtolower($key);

        // key exists?
        if (!array_key_exists($key, $configs))
        {
          throw new sfParseException(sprintf('Configuration file "%s" specifies key "%s" which is not a php.ini directive.', $configFiles[0], $key));
        }

//        $warning = sprintf('{sfPhpConfigHandler} php.ini "%s" key is better set to "%s" (current value is "%s" - %s)', $key, var_export($value, true), var_export(ini_get($key), true), $this->get_ini_path());
// FIXME: sfContext is not yet initialized, so the logger is a sfNoLogger instance.
//        $data[] = sprintf("if (ini_get('%s') != %s)\n{\n  sfContext::getInstance()->getLogger()->warning('%s');\n}\n", $key, var_export($value, true), str_replace("'", "\\'", $warning));
      }
    }

    // check for some extensions
    if (isset($config['extensions']))
    {
      foreach ($config['extensions'] as $extension_name)
      {
        if (!extension_loaded($extension_name))
        {
          throw new sfInitializationException(sprintf('Configuration file "%s" specifies that the PHP extension "%s" should be loaded (%s).', $configFiles[0], $extension_name, $this->get_ini_path()));
        }
      }
    }

    // compile data
    $retval = sprintf("<?php\n".
                      "// auto-generated by sfPhpConfigHandler\n".
                      "// date: %s\n%s\n", date('Y/m/d H:i:s'), implode("\n", $data));

    return $retval;
  }

  /**
   * Gets the php.ini path used by PHP.
   *
   * @return string the php.ini path
   */
  protected function get_ini_path()
  {
    $cfg_path = get_cfg_var('cfg_file_path');
    if ($cfg_path == '')
    {
      $ini_path = 'WARNING: system is not using a php.ini file';
    }
    else
    {
      $ini_path = sprintf('php.ini location: "%s"', $cfg_path);
    }

    return $ini_path;
  }
}
