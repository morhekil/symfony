<?php

/**
 * fillInFilter actions.
 *
 * @package    project
 * @subpackage fillInFilter
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class fillInFilterActions extends sfActions
{
  public function executeForward()
  {
    if ($this->getRequest()->getMethod() === sfRequest::POST)
    {
      $this->forward('fillInFilter', 'done');
    }
  }

  public function executeDone()
  {
  }

  public function handleErrorForward()
  {
    return sfView::SUCCESS;
  }
}
