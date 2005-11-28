<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfSecurityConfigHandler allows you to configure action security.
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfSecurityConfigHandler extends sfYamlConfigHandler
{
  /**
   * Execute this configuration handler.
   *
   * @param string An absolute filesystem path to a configuration file.
   *
   * @return string Data to be written to a cache file.
   *
   * @throws <b>sfConfigurationException</b> If a requested configuration file does not exist or is not readable.
   * @throws <b>sfParseException</b> If a requested configuration file is improperly formatted.
   * @throws <b>sfInitializationException</b> If a view.yml key check fails.
   */
  public function & execute ($configFile, $param = array())
  {
    // set our required categories list and initialize our handler
    $categories = array('required_categories' => array('all'));
    $this->initialize($categories);

    // parse the yaml
    $this->config = $this->parseYaml($configFile);

    // get default configuration
    $this->defaultConfig = array();
    $defaultConfigFile = SF_APP_CONFIG_DIR.'/'.basename($configFile);
    if (is_readable($defaultConfigFile))
    {
      $categories = array('required_categories' => array('default'));
      $this->initialize($categories);

      $this->defaultConfig = $this->parseYaml($defaultConfigFile);
    }

    // iterate through all action names
    $mergedConfig = array();
    foreach ($this->config as $actionName => $values)
    {
      $mergedConfig[$actionName] = array(
        'is_secure'   => $this->getConfigValue('is_secure', $actionName),
        'credentials' => $this->getConfigValue('credentials', $actionName),
      );
    }

    // compile data
    $retval = "<?php\n".
              "// auth-generated by sfSecurityConfigHandler\n".
              "// date: %s\n\$this->security = %s\n?>";
    $retval = sprintf($retval, date('m/d/Y H:i:s'), var_export($mergedConfig, true));

    return $retval;
  }
}

?>