<?php

/**
 * A timestampable implementation BC with symfony <= 1.2.
 * 
 * @package     sfPropelPlugin
 * @subpackage  behavior
 * @author      Kris Wallsmith <kris.wallsmith@symfony-project.com>
 * @version     SVN: $Id$
 */
class SfPropelBehaviorTimestampable extends SfPropelBehaviorBase
{
  protected
    $parameters = array(
      'create_column' => null,
      'update_column' => null,
    );

  public function preInsert()
  {
    if ($this->isDisabled())
    {
      return;
    }

    if ($column = $this->getParameter('create_column'))
    {
      return <<<EOF
if (!\$this->isColumnModified({$this->getTable()->getColumn($column)->getConstantName()}))
{
  \$this->set{$this->getTable()->getColumn($column)->getPhpName()}(time());
}

EOF;
    }
  }

  public function preSave()
  {
    if ($this->isDisabled())
    {
      return;
    }

    if ($column = $this->getParameter('update_column'))
    {
      return <<<EOF
if (\$this->isModified() && !\$this->isColumnModified({$this->getTable()->getColumn($column)->getConstantName()}))
{
  \$this->set{$this->getTable()->getColumn($column)->getPhpName()}(time());
}

EOF;
    }
  }
}
