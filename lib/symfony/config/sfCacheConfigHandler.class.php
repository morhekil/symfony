<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfCacheConfigHandler allows you to configure cache.
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfCacheConfigHandler extends sfYamlConfigHandler
{
  private
    $moduleName  = '',
    $cacheConfig = array();

  /**
   * Execute this configuration handler.
   *
   * @param string An absolute filesystem path to a configuration file.
   *
   * @return string Data to be written to a cache file.
   *
   * @throws <b>sfConfigurationException</b> If a requested configuration file does not exist or is not readable.
   * @throws <b>sfParseException</b> If a requested configuration file is improperly formatted.
   * @throws <b>sfInitializationException</b> If a cache.yml key check fails.
   */
  public function & execute ($configFile, $param = array())
  {
    // set our required categories list and initialize our handler
    $categories = array('required_categories' => array());
    $this->initialize($categories);

    // parse the yaml
    $this->config = $this->parseYaml($configFile);

    // init our data array
    $data = array();

    $this->moduleName = $param['moduleName'];

    // get default configuration
    $this->defaultConfig = array();
    $defaultConfigFile = sfConfig::get('sf_app_config_dir').'/'.basename($configFile);
    if (is_readable($defaultConfigFile))
    {
      $categories = array('required_categories' => array('default'));
      $this->initialize($categories);

      $this->defaultConfig = $this->parseYaml($defaultConfigFile);
    }

    // iterate through all action names
    $first = true;
    foreach ($this->config as $actionName => $values)
    {
      if ($actionName == 'all')
      {
        continue;
      }

      $data[] = ($first ? '' : 'else ')."if (\$actionName == '$actionName')\n".
                "{\n";

      if ($this->getConfigValue('activate', $actionName))
      {
        $data[] = $this->addCache($actionName);
      }

      $data[] = "}\n";

      $first = false;
    }

    // general cache configuration
    if ($this->getConfigValue('activate', $actionName))
    {
      $data[] = ($first ? '' : "else\n{")."\n";
      $data[] = $this->addCache('DEFAULT');
      $data[] = ($first ? '' : "}")."\n";
    }

    // compile data
    $retval = "<?php\n".
              "// auth-generated by sfCacheConfigHandler\n".
              "// date: %s\n%s\n?>";
    $retval = sprintf($retval, date('m/d/Y H:i:s'), implode('', $data));

    return $retval;
  }

  private function addCache($actionName = '')
  {
    $data = array();

    // cache type for this action (slot or page)
    $type = $this->getConfigValue('type', $actionName);

    // lifetime
    $lifeTime = $this->getConfigValue('lifetime', $actionName);

    // uri
    $uri = $this->getConfigValue('uri', $actionName);

    // add cache information to cache manager
    if ($uri)
    {
      $tmp = "  \$cacheManager->addCache('%s', '%s', '%s', %s, '%s');\n";
      $data[] = sprintf($tmp, $this->moduleName, $actionName, $type, $lifeTime, $uri);
    }
    else
    {
      $tmp = "  \$cacheManager->addCache('%s', '%s', '%s', %s);\n";
      $data[] = sprintf($tmp, $this->moduleName, $actionName, $type, $lifeTime);
    }

    return implode("\n", $data);
  }
}

?>