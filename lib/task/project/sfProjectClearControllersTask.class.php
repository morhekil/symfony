<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Clears all non production environment controllers.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfProjectClearControllersTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->aliases = array('clear-controllers');
    $this->namespace = 'project';
    $this->name = 'clear-controllers';
    $this->briefDescription = 'Clears all non production environment controllers';

    $this->detailedDescription = <<<EOF
The [project:clear-controllers|INFO] task clears all non production environment
controllers:

  [./symfony project:clear-controllers|INFO]

You can use this task on a production server to remove all front
controller scripts except the production ones.

If you have two applications named [frontend|COMMENT] and [backend|COMMENT],
you have four default controller scripts in [web/|COMMENT]:

  [index.php
  frontend_dev.php
  backend.php
  backend_dev.php|INFO]

After executing the [project:clear-controllers|COMMENT] task, two front
controller scripts are left in [web/|COMMENT]:

  [index.php
  backend.php|INFO]

Those two controllers are safe because debug mode and the web debug
toolbar are disabled.
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $finder = sfFinder::type('file')->ignore_version_control()->maxdepth(1)->name('*.php');
    foreach ($finder->in(sfConfig::get('sf_web_dir')) as $controller)
    {
      $contents = file_get_contents($controller);
      preg_match('/\'SF_APP\',[\s]*\'(.*)\'\)/', $contents, $foundApp);
      preg_match('/\'SF_ENVIRONMENT\',[\s]*\'(.*)\'\)/', $contents, $env);

      // Remove file if it has found an application and the environment is not production
      if (isset($foundApp[1]) && isset($env[1]) && $env[1] != 'prod')
      {
        $this->getFilesystem()->remove($controller);
      }
    }
  }
}
