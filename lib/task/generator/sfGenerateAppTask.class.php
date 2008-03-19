<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/sfGeneratorBaseTask.class.php');

/**
 * Generates a new application.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfGenerateAppTask extends sfGeneratorBaseTask
{
  /**
   * @see sfTask
   */
  protected function doRun(sfCommandManager $commandManager, $options)
  {
    $this->process($commandManager, $options);

    $this->checkProjectExists();

    return $this->execute($commandManager->getArgumentValues(), $commandManager->getOptionValues());
  }

  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
    ));

    $this->aliases = array('init-app');
    $this->namespace = 'generate';
    $this->name = 'app';

    $this->briefDescription = 'Generates a new application';

    $this->detailedDescription = <<<EOF
The [generate:app|INFO] task creates the basic directory structure
for a new application in the current project:

  [./symfony generate:app frontend|INFO]

This task also creates two front controller scripts in the
[web/|COMMENT] directory:

  [web/%application%.php|INFO]     for the production environment
  [web/%application%_dev.php|INFO] for the development environment

For the first application, the production environment script is named
[index.php|COMMENT].

If an application with the same name already exists,
it throws a [sfCommandException|COMMENT].
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $app = $arguments['application'];

    $appDir = sfConfig::get('sf_apps_dir').'/'.$app;

    if (is_dir($appDir))
    {
      throw new sfCommandException(sprintf('The application "%s" already exists.', $appDir));
    }

    // Create basic application structure
    $finder = sfFinder::type('any')->ignore_version_control()->discard('.sf');
    $this->getFilesystem()->mirror(dirname(__FILE__).'/skeleton/app/app', $appDir, $finder);

    // Create $app.php or index.php if it is our first app
    $indexName = 'index';
    $firstApp = !file_exists(sfConfig::get('sf_web_dir').'/index.php');
    if (!$firstApp)
    {
      $indexName = $app;
    }

    // Set no_script_name value in settings.yml for production environment
    $finder = sfFinder::type('file')->name('settings.yml');
    $this->getFilesystem()->replaceTokens($finder->in($appDir.'/config'), '##', '##', array('NO_SCRIPT_NAME' => ($firstApp ? 'on' : 'off')));

    $this->getFilesystem()->copy(dirname(__FILE__).'/skeleton/app/web/index.php', sfConfig::get('sf_web_dir').'/'.$indexName.'.php');
    $this->getFilesystem()->copy(dirname(__FILE__).'/skeleton/app/web/index.php', sfConfig::get('sf_web_dir').'/'.$app.'_dev.php');

    $this->getFilesystem()->replaceTokens(sfConfig::get('sf_web_dir').'/'.$indexName.'.php', '##', '##', array(
      'APP_NAME'    => $app,
      'ENVIRONMENT' => 'prod',
      'IS_DEBUG'    => 'false',
    ));

    $this->getFilesystem()->replaceTokens(sfConfig::get('sf_web_dir').'/'.$app.'_dev.php', '##', '##', array(
      'APP_NAME'    => $app,
      'ENVIRONMENT' => 'dev',
      'IS_DEBUG'    => 'true',
    ));

    $this->getFilesystem()->copy(dirname(__FILE__).'/skeleton/app/app/config/ApplicationConfiguration.class.php', $appDir.'/config/'.$app.'Configuration.class.php');

    $this->getFilesystem()->replaceTokens($appDir.'/config/'.$app.'Configuration.class.php', '##', '##', array('APP_NAME' => $app));

    $fixPerms = new sfProjectPermissionsTask($this->dispatcher, $this->formatter);
    $fixPerms->setCommandApplication($this->commandApplication);
    $fixPerms->run();

    // Create test dir
    $this->getFilesystem()->mkdirs(sfConfig::get('sf_test_dir').'/functional/'.$app);
  }
}
