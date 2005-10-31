<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project>
 * (c) 2004, 2005 Sean Kerr.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * sfActionStack keeps a list of all requested actions and provides accessor
 * methods for retrieving individual entries.
 *
 * @package    symfony
 * @subpackage action
 * @author     Fabien Potencier <fabien.potencier@symfony-project>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */
class sfActionStack
{
  private
    $stack = array();

  /**
   * Add an entry.
   *
   * @param string   A module name.
   * @param string   An action name.
   * @param sfAction An sfAction implementation instance.
   *
   * @return sfActionEntry sfActionEntry instance
   */
  public function addEntry ($moduleName, $actionName, $actionInstance)
  {
    // create our action stack entry and add it to our stack
    $actionEntry = new sfActionStackEntry($moduleName, $actionName, $actionInstance);

    $this->stack[] = $actionEntry;
    
    return $actionEntry;
  }

  /**
   * Retrieve the entry at a specific index.
   *
   * @param int An entry index.
   *
   * @return ActionStackEntry An action stack entry implementation.
   */
  public function getEntry ($index)
  {
    $retval = null;

    if ($index > -1 && $index < count($this->stack))
      $retval = $this->stack[$index];

    return $retval;
  }

  /**
   * Retrieve the first entry.
   *
   * @return ActionStackEntry An action stack entry implementation.
   */
  public function getFirstEntry ()
  {
    $retval = null;

    if (isset($this->stack[0]))
      $retval = $this->stack[0];

    return $retval;
  }

  /**
   * Retrieve the last entry.
   *
   * @return ActionStackEntry An action stack entry implementation.
   */
  public function getLastEntry ()
  {
    $count  = count($this->stack);
    $retval = null;

    if (isset($this->stack[0]))
      $retval = $this->stack[$count - 1];

    return $retval;
  }

  /**
   * Retrieve the size of this stack.
   *
   * @return int The size of this stack.
   */
  public function getSize ()
  {
    return count($this->stack);
  }
}

?>