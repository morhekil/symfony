<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Upgrades all 1.0 singletons.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfSingletonUpgrade extends sfUpgrade
{
  public function upgrade()
  {
    $this->upgradeFactoryCalls();
    $this->upgradeFactoryConfiguration();
    $this->upgradeSettingConfiguration();
  }

  public function upgradeFactoryCalls()
  {
    $phpFinder = $this->getFinder('file')->name('*.php');
    foreach ($phpFinder->in($this->getProjectClassDirectories()) as $file)
    {
      $content = file_get_contents($file);
      $content = str_replace(
        array('sfI18N::getInstance()', 'sfRouting::getInstance()', 'sfLogger::getInstance()'),
        array('sfContext::getInstance()->getI18N()', 'sfContext::getInstance()->getRouting()', 'sfContext::getInstance()->getLogger()'),
        $content, $count
      );

      if ($count)
      {
        $this->dispatcher->notify(new sfEvent($this, 'command.log', array($this->formatter->formatSection('singleton', sprintf('Migrating %s', $file)))));
        file_put_contents($file, $content);
      }
    }
  }

  public function upgradeFactoryConfiguration()
  {
    $phpFinder = $this->getFinder('file')->name('factories.yml');
    foreach ($phpFinder->in($this->getProjectConfigDirectories()) as $file)
    {
      $content = file_get_contents($file);
      if (false !== strpos($content, 'sfNoLogger'))
      {
        continue;
      }

      $content =
<<<EOF
prod:
  logger:
    class:   sfNoLogger
    param:
      level:   err
      loggers: ~

EOF
      .$content;

      $this->dispatcher->notify(new sfEvent($this, 'command.log', array($this->formatter->formatSection('factories.yml', sprintf('Migrating %s', $file)))));
      file_put_contents($file, $content);
    }
  }

  public function upgradeSettingConfiguration()
  {
    $phpFinder = $this->getFinder('file')->name('settings.yml');
    foreach ($phpFinder->in($this->getProjectConfigDirectories()) as $file)
    {
      $content = file_get_contents($file);
      if (false !== strpos($content, 'logging_enabled'))
      {
        continue;
      }

      $content = preg_replace('/(prod\:\s+\.settings\:)/s', "$1\n    logging_enabled: off", $content);

      $this->dispatcher->notify(new sfEvent($this, 'command.log', array($this->formatter->formatSection('settings.yml', sprintf('Migrating %s', $file)))));
      file_put_contents($file, $content);
    }
  }
}
