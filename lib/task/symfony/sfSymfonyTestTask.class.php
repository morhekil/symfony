<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Launches the symfony test suite.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfTestAllTask.class.php 8148 2008-03-29 07:58:59Z fabien $
 */
class sfSymfonyTestTask extends sfTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('update-autoloader', 'u', sfCommandOption::PARAMETER_NONE, 'Update the sfCoreAutoload class'),
      new sfCommandOption('only-failed', 'f', sfCommandOption::PARAMETER_NONE, 'Only run tests that failed last time'),
    ));

    $this->namespace = 'symfony';
    $this->name = 'test';
    $this->briefDescription = 'Launches the symfony test suite';

    $this->detailedDescription = <<<EOF
The [test:all|INFO] task launches the symfony test suite:

  [./symfony symfony:test|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    require_once(dirname(__FILE__).'/../../vendor/lime/lime.php');
    require_once(dirname(__FILE__).'/lime_symfony.php');

    // cleanup
    require_once(dirname(__FILE__).'/../../util/sfToolkit.class.php');
    if ($files = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'/sf_autoload_unit_*'))
    {
      foreach ($files as $file)
      {
        unlink($file);
      }
    }

    // update sfCoreAutoload
    if ($options['update-autoloader'])
    {
      require_once(dirname(__FILE__).'/../../autoload/sfCoreAutoload.class.php');
      sfCoreAutoload::make();
    }

    $status = false;
    $statusFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.sprintf('/.test_symfony_%s_status', md5(dirname(__FILE__)));
    if ($options['only-failed'])
    {
      if (file_exists($statusFile))
      {
        $status = unserialize(file_get_contents($statusFile));
      }
    }

    $h = new lime_symfony(new lime_output($options['color']));
    $h->base_dir = realpath(dirname(__FILE__).'/../../../test');

    if ($status)
    {
      foreach ($status as $file)
      {
        $h->register($file);
      }
    }
    else
    {
      $h->register(sfFinder::type('file')->prune('fixtures')->name('*Test.php')->in(array_merge(
        // unit tests
        array($h->base_dir.'/unit'),
        glob($h->base_dir.'/../lib/plugins/*/test/unit'),

        // functional tests
        array($h->base_dir.'/functional'),
        glob($h->base_dir.'/../lib/plugins/*/test/functional'),

        // other tests
        array($h->base_dir.'/other')
      )));
    }

    $ret = $h->run() ? 0 : 1;

    file_put_contents($statusFile, serialize($h->get_failed_files()));

    return $ret;
  }
}
