<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Disables an application in a given environment.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfProjectDisableTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('env', sfCommandArgument::REQUIRED, 'The environment name'),
    ));

    $this->aliases = array('disable');
    $this->namespace = 'project';
    $this->name = 'disable';
    $this->briefDescription = 'Disables an application in a given environment';

    $this->detailedDescription = <<<EOF
The [project:disable|INFO] task disables an application for a specific environment:

  [./symfony project:disable frontend prod|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $app = $arguments['application'];
    $env = $arguments['env'];

    $lockFile = $app.'_'.$env.'.lck';
    if (file_exists(sfConfig::get('sf_cache_dir').'/'.$lockFile))
    {
      $this->logSection('enable', sprintf('%s [%s] is currently DISABLED', $app, $env));
    }

    $this->getFilesystem()->touch($lockFile);

    $this->logSection('enable', sprintf('%s [%s] has been DISABLED', $app, $env));
  }
}
