<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@gmail.com>
 * (c) 2004, 2005 Sean Kerr.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfValidatorException is thrown when an error occurs in a validator.
 *
 * @package    symfony
 * @subpackage exception
 * @author     Fabien Potencier <fabien.potencier@gmail.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */
class sfValidatorException extends sfException
{
  /**
   * Class constructor.
   *
   * @param string The error message.
   * @param int    The error code.
   */
  public function __construct ($message = null, $code = 0)
  {
    $this->setName('sfValidatorException');
    parent::__construct($message, $code);
  }
}

?>