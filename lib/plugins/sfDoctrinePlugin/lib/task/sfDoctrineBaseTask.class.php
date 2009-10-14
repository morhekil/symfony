<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) Jonathan H. Wage <jonwage@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class for all symfony Doctrine tasks.
 *
 * @package    symfony
 * @subpackage doctrine
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @version    SVN: $Id$
 */
abstract class sfDoctrineBaseTask extends sfBaseTask
{
  /**
   * Returns an array of configuration variables for the Doctrine CLI.
   *
   * @return array $config
   *
   * @see sfDoctrinePluginConfiguration::getCliConfig()
   */
  public function getCliConfig()
  {
    return $this->configuration->getPluginConfiguration('sfDoctrinePlugin')->getCliConfig();
  }

  /**
   * Calls a Doctrine CLI command.
   *
   * @param string $task Name of the Doctrine task to call
   * @param array  $args Arguments for the task
   *
   * @see sfDoctrineCli
   */
  public function callDoctrineCli($task, $args = array())
  {
    $config = $this->getCliConfig();

    $arguments = array('./symfony', $task);

    foreach ($args as $key => $arg)
    {
      if (isset($config[$key]))
      {
        $config[$key] = $arg;
      }
      else
      {
        $arguments[] = $arg;
      }
    }

    $cli = new sfDoctrineCli($config);
    $cli->setDispatcher($this->dispatcher);
    $cli->setFormatter($this->formatter);
    $cli->run($arguments);
  }

  /**
   * Merges all project and plugin schema files into one.
   *
   * Schema files are merged similar to how other configuration files are in
   * symfony, utilizing a configuration cascade. Files later in the cascade
   * can change values from earlier in the cascade.
   *
   * The order in which schema files are processed is like so:
   *
   *  1. Plugin schema files
   *    * Plugins are processed in the order which they were enabled in ProjectConfiguration
   *    * Each plugin's schema files are processed in alphabetical order
   *  2. Project schema files
   *    * Project schema files are processed in alphabetical order
   *
   * A schema file is any file saved in a plugin or project's config/doctrine/
   * directory that matches the "*.yml" glob.
   *
   * @return string Absolute path to the consolidated schema file
   */
  protected function prepareSchemaFile($yamlSchemaPath)
  {
    $models = array();
    $finder = sfFinder::type('file')->name('*.yml')->sort_by_name();

    // plugin models
    foreach ($this->configuration->getPlugins() as $name)
    {
      $plugin = $this->configuration->getPluginConfiguration($name);
      foreach ($finder->in($plugin->getRootDir().'/config/doctrine') as $schema)
      {
        $pluginModels = sfYaml::load($schema);
        $globals = $this->filterSchemaGlobals($pluginModels);

        foreach ($pluginModels as $model => $definition)
        {
          // merge globals
          $definition = array_merge($globals, $definition);

          // merge this model into the schema
          $models[$model] = isset($models[$model]) ? sfToolkit::arrayDeepMerge($models[$model], $definition) : $definition;

          // the first plugin to define this model gets the package
          if (!isset($models[$model]['package']))
          {
            $models[$model]['package'] = $plugin->getName().'.lib.model.doctrine';
            $models[$model]['package_custom_path'] = $plugin->getRootDir().'/lib/model/doctrine';
          }
        }
      }
    }

    // project models
    foreach ($finder->in($yamlSchemaPath) as $schema)
    {
      $projectModels = sfYaml::load($schema);
      $globals = $this->filterSchemaGlobals($projectModels);

      foreach ($projectModels as $model => $definition)
      {
        $definition = array_merge($globals, $definition);
        $models[$model] = isset($models[$model]) ? sfToolkit::arrayDeepMerge($models[$model], $definition) : $definition;
      }
    }

    // create one consolidated schema file
    $file = tempnam(sys_get_temp_dir(), 'doctrine_schema_').'.yml';
    $this->logSection('file+', $file);
    file_put_contents($file, sfYaml::dump($models, 4));

    return $file;
  }

  /**
   * Removes and returns globals from the supplied array of models.
   *
   * @param array $models An array of model definitions
   *
   * @return array
   */
  protected function filterSchemaGlobals(& $models)
  {
    $globals = array();
    $globalKeys = Doctrine_Import_Schema::getGlobalDefinitionKeys();

    foreach ($models as $key => $value)
    {
      if (in_array($key, $globalKeys))
      {
        $globals[$key] = $value;
        unset($models[$key]);
      }
    }

    return $globals;
  }
}
