<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfGenerator is the abstract base class for all generators.
 *
 * @package    symfony
 * @subpackage generator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
abstract class sfGenerator
{
  protected
    $generatorManager    = null,
    $generatedModuleName = '',
    $moduleName          = '';

  public function initialize($generatorManager)
  {
    $this->generatorManager = $generatorManager;
  }

  abstract public function generate($class, $param);

  protected function generatePhpFiles($generatedModuleName, $template_dir)
  {
    // eval actions file
    $retval = $this->evalTemplate($template_dir.'/actions/actions.class.php', __CLASS__);

    // save actions class
    $this->getGeneratorManager()->getCache()->set('actions.class.php', $generatedModuleName.DIRECTORY_SEPARATOR.'actions', $retval);

    // generate template files
    $templates = array('listSuccess', 'editSuccess', 'showSuccess');
    foreach ($templates as $template)
    {
      // eval template file
      $retval = $this->evalTemplate($template_dir.'/templates/'.$template.'.php', __CLASS__);

      // save actions class
      $this->getGeneratorManager()->getCache()->set($template.'.php', $generatedModuleName.DIRECTORY_SEPARATOR.'templates', $retval);
    }
  }

  protected function evalTemplate($template_file, $clazz = __CLASS__)
  {
    // eval template template file
    ob_start();
    require($template_file);
    $content = ob_get_clean();

    // replace [?php and ?]
    $content = $this->replacePhpMarks($content);

    $retval = "<?php\n".
              "// auto-generated by $clazz\n".
              "// date: %s\n?>\n%s";
    $retval = sprintf($retval, date('m/d/Y H:i:s'), $content);

    return $retval;
  }

  protected function replacePhpMarks($text)
  {
    // replace [?php and ?]
    $text = str_replace('[?php', '<?php',      $text);
    $text = str_replace('[?=',   '<?php echo', $text);
    $text = str_replace('?]',    '?>',         $text);

    return $text;
  }

  protected function getGeneratorManager()
  {
    return $this->generatorManager;
  }

  public function getGeneratedModuleName()
  {
    return $this->generatedModuleName;
  }

  public function setGeneratedModuleName($module_name)
  {
    $this->generatedModuleName = $module_name;
  }

  public function getModuleName()
  {
    return $this->moduleName;
  }

  public function setModuleName($module_name)
  {
    $this->moduleName = $module_name;
  }
}

?>