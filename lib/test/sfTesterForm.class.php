<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfTesterForm implements tests for forms submitted by the user.
 *
 * @package    symfony
 * @subpackage test
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfTesterForm extends sfTester
{
  protected
    $form = null;

  /**
   * Constructor.
   *
   * @param sfTestFunctionalBase $browser A browser
   * @param lime_test            $tester  A tester object
   */
  public function __construct(sfTestFunctionalBase $browser, $tester)
  {
    parent::__construct($browser, $tester);

    $this->browser->addListener('template.filter_parameters', array($this, 'filterTemplateParameters'));
  }

  /**
   * Prepares the tester.
   */
  public function prepare()
  {
    $this->form = null;
  }

  /**
   * Initiliazes the tester.
   */
  public function initialize()
  {
    if (is_null($this->form))
    {
      $action = $this->browser->getContext()->getActionStack()->getLastEntry()->getActionInstance();

      foreach ($action->getVarHolder()->getAll() as $name => $value)
      {
        if ($value instanceof sfForm && $value->isBound())
        {
          $this->form = $value;
          break;
        }
      }
    }
  }

  /**
   * Returns the current form.
   *
   * @return sfForm The current sfForm form instance
   */
  public function getForm()
  {
    return $this->form;
  }

  /**
   * Tests if the submitted form has some error.
   *
   * @param  Boolean|integer $value Whether to check if the form has error or not, or the number of errors
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function hasErrors($value = true)
  {
    if (is_null($this->form))
    {
      throw new LogicException('no form has been submitted.');
    }

    if (is_int($value))
    {
      $this->tester->is(count($this->form->getErrorSchema()), $value, sprintf('the submitted form has "%s" errors.', $value));
    }
    else
    {
      $this->tester->is($this->form->hasErrors(), $value, 'the submitted form has some errors.');
    }

    return $this->getObjectToReturn();
  }

  /**
   * Tests if the submitted form has a specific error.
   *
   * @param  string $field   The field name to check for an error (null for global errors)
   * @param  mixed  $message The error message or the number of errors for the field (optional)
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function hasGlobalError($value = true)
  {
    return $this->isError(null, $value);
  }

  /**
   * Tests if the submitted form has a specific error.
   *
   * @param  string $field   The field name to check for an error (null for global errors)
   * @param  mixed  $message The error message or the number of errors for the field (optional)
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function isError($field, $value = true)
  {
    if (is_null($this->form))
    {
      throw new LogicException('no form has been submitted.');
    }

    if (is_null($field))
    {
      $error = new sfValidatorErrorSchema(new sfValidatorPass(), $this->form->getGlobalErrors());
    }
    else
    {
      $error = $this->form[$field]->getError();
    }

    if (false === $value)
    {
      $this->tester->ok(!$error || 0 == count($error), sprintf('the submitted form has no "%s" error.', $field));
    }
    else if (true === $value)
    {
      $this->tester->ok($error && count($error) > 0, sprintf('the submitted form has a "%s" error.', $field));
    }
    else if (is_int($value))
    {
      $this->tester->ok($error && count($error) == $value, sprintf('the submitted form has %s "%s" error(s).', $value, $field));
    }
    else if (preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $value, $match))
    {
      if (!$error)
      {
        $this->tester->fail(sprintf('the submitted form has a "%s" error.', $field));
      }
      else
      {
        if ($match[1] == '!')
        {
          $this->tester->unlike($error->__toString(), substr($value, 1), sprintf('the submitted form has a "%s" error that does not match "%s".', $field, $value));
        }
        else
        {
          $this->tester->like($error->__toString(), $value, sprintf('the submitted form has a "%s" error that matches "%s".', $field, $value));
        }
      }
    }
    else
    {
      if (!$error)
      {
        $this->tester->fail(sprintf('the submitted form has a "%s" error.', $field));
      }
      else
      {
        $this->tester->is($error->__toString(), $value, sprintf('the submitted form has a "%s" error (%s).', $field, $value));
      }
    }

    return $this->getObjectToReturn();
  }

  /**
   * Outputs some debug information about the current submitted form.
   */
  public function debug()
  {
    if (is_null($this->form))
    {
      throw new LogicException('no form has been submitted.');
    }

    print $this->tester->error('Form debug');

    print sprintf("Submitted values: %s\n", str_replace("\n", '', var_export($this->form->getTaintedValues(), true)));
    print sprintf("Errors: %s\n", $this->form->getErrorSchema());

    exit(1);
  }

  /**
   * Listens to the template.filter_parameters event to get the submitted form object.
   *
   * @param sfEvent $event      The event
   * @param array   $parameters An array of parameters passed to the template
   *
   * @return array The array of parameters passed to the template
   */
  public function filterTemplateParameters(sfEvent $event, $parameters)
  {
    if (!isset($parameters['sf_type']))
    {
      return $parameters;
    }

    if ('action' == $parameters['sf_type'])
    {
      foreach ($parameters as $key => $value)
      {
        if ($value instanceof sfForm && $value->isBound())
        {
          $this->form = $value;
          break;
        }
      }
    }

    return $parameters;
  }
}
