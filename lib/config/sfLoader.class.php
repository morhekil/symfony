<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage util
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfLoader
{
  static public function getModelDirs()
  {
    $dirs = array(sfConfig::get('sf_lib_dir').'/model' ? sfConfig::get('sf_lib_dir').'/model' : 'lib/model'); // project
    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/lib/model'))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                                                // plugins
    }

    return $dirs;
  }

  static public function getControllerDirs($moduleName)
  {
    $suffix = $moduleName.'/'.sfConfig::get('sf_app_module_action_dir_name');

    $dirs = array();
    foreach (sfConfig::get('sf_module_dirs', array()) as $key => $value)
    {
      $dirs[$key.'/'.$suffix] = $value;
    }

    $dirs[sfConfig::get('sf_app_module_dir').'/'.$suffix] = false;                                     // application

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$suffix))
    {
      $dirs = array_merge($dirs, array_combine($pluginDirs, array_fill(0, count($pluginDirs), true))); // plugins
    }

    $dirs[sfConfig::get('sf_symfony_data_dir').'/modules/'.$suffix] = true;                            // core modules

    return $dirs;
  }

  static public function getTemplateDirs($moduleName)
  {
    $suffix = $moduleName.'/'.sfConfig::get('sf_app_module_template_dir_name');

    $dirs = array();
    foreach (sfConfig::get('sf_module_dirs', array()) as $key => $value)
    {
      $dirs[] = $key.'/'.$suffix;
    }

    $dirs[] = sfConfig::get('sf_app_module_dir').'/'.$suffix;                        // application

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$suffix))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                       // plugins
    }

    $dirs[] = sfConfig::get('sf_symfony_data_dir').'/modules/'.$suffix;              // core modules
    $dirs[] = sfConfig::get('sf_module_cache_dir').'/auto'.ucfirst($suffix);         // generated templates in cache

    return $dirs;
  }

  static public function getTemplateDir($moduleName, $templateFile)
  {
    $dirs = self::getTemplateDirs($moduleName);
    foreach ($dirs as $dir)
    {
      if (is_readable($dir.'/'.$templateFile))
      {
        return $dir;
      }
    }

    return null;
  }

  static public function getI18NDir($moduleName)
  {
    $suffix = $moduleName.'/'.sfConfig::get('sf_app_module_i18n_dir_name');

    // application
    $dir = sfConfig::get('sf_app_module_dir').'/'.$suffix;
    if (is_dir($dir))
    {
      return $dir;
    }

    // plugins
    $dirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$suffix);
    if (isset($dirs[0]))
    {
      return $dirs[0];
    }
  }

  static public function getGeneratorTemplateDirs($class, $theme)
  {
    $dirs = array();

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/data/generator/'.$class.'/'.$theme.'/template'))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                                                // plugin
    }

    $dirs[] = sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/template';                       // project
    $dirs[] = sfConfig::get('sf_symfony_data_dir').'/generator/'.$class.'/default/template';                  // default theme

    return $dirs;
  }

  static public function getGeneratorSkeletonDirs($class, $theme)
  {
    $dirs = array();

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/data/generator/'.$class.'/'.$theme.'/skeleton'))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                                                // plugin
    }

    $dirs[] = sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/skeleton';                       // project
    $dirs[] = sfConfig::get('sf_symfony_data_dir').'/generator/'.$class.'/default/skeleton';                  // default theme

    return $dirs;
  }

  static public function getGeneratorTemplate($class, $theme, $path)
  {
    $dirs = self::getGeneratorTemplateDirs($class, $theme);
    foreach ($dirs as $dir)
    {
      if (is_readable($dir.'/'.$path))
      {
        return $dir.'/'.$path;
      }
    }

    throw new sfException(sprintf('Unable to load "%s" generator template in: %s', $path, implode(', ', $dirs)));
  }

  static public function getConfigDirs($configPath)
  {
    $globalConfigPath = basename(dirname($configPath)).'/'.basename($configPath);

    $dirs = array(
      sfConfig::get('sf_symfony_data_dir').'/'.$globalConfigPath,                    // symfony
      sfConfig::get('sf_symfony_data_dir').'/'.$configPath,                          // core modules
    );

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/'.$globalConfigPath))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                       // main plugins config
    }

    $dirs = array_merge($dirs, array(
      sfConfig::get('sf_root_dir').'/'.$globalConfigPath,                            // project
      sfConfig::get('sf_root_dir').'/'.$configPath,                            // project
      sfConfig::get('sf_app_dir').'/'.$globalConfigPath,                             // application
      sfConfig::get('sf_app_dir').'/'.$configPath,                             // application
    ));

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/'.$configPath))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                       // modules in plugins
    }

    $dirs = array_merge($dirs, array(
      sfConfig::get('sf_cache_dir').'/'.$configPath,                                 // generated modules
      sfConfig::get('sf_app_dir').'/'.$configPath,                                   // module
    ));

    return array_values(array_unique($dirs));
  }

  static public function getHelperDirs($moduleName = '')
  {
    $dirs = array();

    if ($moduleName)
    {
      $dirs[] = sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/'.sfConfig::get('sf_app_module_lib_dir_name').'/helper'; // module

      if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/modules/'.$moduleName.'/lib/helper'))
      {
        $dirs = array_merge($dirs, $pluginDirs);                                                                              // module plugins
      }
    }

    $dirs[] = sfConfig::get('sf_app_lib_dir').'/helper';                                                                      // application

    $dirs[] = sfConfig::get('sf_lib_dir').'/helper';                                                                          // project

    if ($pluginDirs = glob(sfConfig::get('sf_plugins_dir').'/*/lib/helper'))
    {
      $dirs = array_merge($dirs, $pluginDirs);                                                                                // plugins
    }

    $dirs[] = sfConfig::get('sf_symfony_lib_dir').'/helper';                                                                  // global

    return $dirs;
  }

  static public function loadHelpers($helpers, $moduleName = '')
  {
    static $loaded = array();

    $dirs = self::getHelperDirs($moduleName);
    foreach ((array) $helpers as $helperName)
    {
      if (isset($loaded[$helperName]))
      {
        continue;
      }

      $fileName = $helperName.'Helper.php';
      foreach ($dirs as $dir)
      {
        $included = false;
        if (is_readable($dir.'/'.$fileName))
        {
          include($dir.'/'.$fileName);
          $included = true;
          break;
        }
      }

      if (!$included)
      {
        // search in the include path
        if ((@include('helper/'.$fileName)) != 1)
        {
          $dirs = array_merge($dirs, explode(PATH_SEPARATOR, get_include_path()));

          // remove sf_root_dir from dirs
          foreach ($dirs as &$dir)
          {
            $dir = str_replace('%SF_ROOT_DIR%', sfConfig::get('sf_root_dir'), $dir);
          }

          throw new sfViewException(sprintf('Unable to load "%sHelper.php" helper in: %s', $helperName, implode(', ', $dirs)));
        }
      }

      $loaded[$helperName] = true;
    }
  }

  static public function loadPluginConfig()
  {
    if ($pluginConfigs = glob(sfConfig::get('sf_plugins_dir').'/*/config/config.php'))
    {
      foreach ($pluginConfigs as $config)
      {
        include($config);
      }
    }
  }
}
