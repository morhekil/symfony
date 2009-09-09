<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfTesterMailer implements tests for the symfony mailer object.
 *
 * @package    symfony
 * @subpackage test
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfTesterUser.class.php 15802 2009-02-26 09:40:27Z fabien $
 */
class sfTesterMailer extends sfTester
{
  protected
    $logger  = null,
    $message = null;

  /**
   * Prepares the tester.
   */
  public function prepare()
  {
  }

  /**
   * Initializes the tester.
   */
  public function initialize()
  {
    $this->logger = $this->browser->getContext()->getMailer()->getTransport()->getLogger();

    if ($this->logger->countMessages())
    {
      $messages = $this->logger->getMessages();

      $this->message = $messages[0];
    }
  }

  /**
   * Tests a user attribute value.
   *
   * @param string $key
   * @param string $value
   * @param string $ns
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function hasSent($nb = null)
  {
    if (is_null($nb))
    {
      $this->tester->ok($this->logger->countMessages() > 0, 'mailer sent some email(s).');
    }
    else
    {
      $this->tester->is($this->logger->countMessages(), $nb, sprintf('mailer sent %s email(s).', $nb));
    }

    return $this->getObjectToReturn();
  }

  /**
   * Outputs some debug information about mails sent during the current request.
   */
  public function debug()
  {
    foreach ($this->logger->getMessages() as $message)
    {
      echo $message->toString()."\n\n";
    }

    exit(1);
  }

  /**
   * Changes the context to use the email corresponding to the given criteria.
   *
   * Recognized criteria:
   *
   *  * to
   *  * position
   *
   * @param array   $criteria An array of criteria
   *
   * @param sfTester A sfTester instance
   */
  public function withMessage($to, $position = 1)
  {
    $matches = 0;
    foreach ($this->logger->getMessages() as $message)
    {
      if (in_array($to, array_keys($message->getTo())))
      {
        $matches++;

        if ($matches == $position)
        {
          $this->message = $message;

          $this->tester->pass(sprintf('switch context to the message number "%s" sent to "%s"', $position, $to));

          return $this;
        }
      }
    }

    $this->tester->fail(sprintf('unable to find a message sent to "%s"', $to));

    return $this;
  }

  /**
   * Tests for a mail message body.
   *
   * @param  string $value
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function checkBody($value)
  {
    if (!$this->message)
    {
      $this->tester->fail('unable to test as no email were sent');
    }

    $body = $this->message->getBody();
    $ok = false;
    $regex = false;
    $mustMatch = true;
    if (preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $value, $match))
    {
      $regex = $value;
      if ($match[1] == '!')
      {
        $mustMatch = false;
        $regex = substr($value, 1);
      }
    }

    if (false !== $regex)
    {
      if ($mustMatch)
      {
        if (preg_match($regex, $body))
        {
          $ok = true;
          $this->tester->pass(sprintf('email body matches "%s"', $value));
        }
      }
      else
      {
        if (preg_match($regex, $body))
        {
          $ok = true;
          $this->tester->fail(sprintf('email body does not match "%s"', $value));
        }
      }
    }
    else if ($body == $value)
    {
      $ok = true;
      $this->tester->pass(sprintf('email body is "%s"', $value));
    }

    if (!$ok)
    {
      if (!$mustMatch)
      {
        $this->tester->pass(sprintf('email body matches "%s"', $value));
      }
      else
      {
        $this->tester->fail(sprintf('email body matches "%s"', $value));
      }
    }

    return $this->getObjectToReturn();
  }

  /**
   * Tests for a mail message header.
   *
   * @param  string $key
   * @param  string $value
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function checkHeader($key, $value)
  {
    if (!$this->message)
    {
      $this->tester->fail('unable to test as no email were sent');
    }

    $headers = array();
    foreach ($this->message->getHeaders()->getAll($key) as $header)
    {
      $headers[] = $header->getFieldBody();
    }
    $current = implode(', ', $headers);
    $ok = false;
    $regex = false;
    $mustMatch = true;
    if (preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $value, $match))
    {
      $regex = $value;
      if ($match[1] == '!')
      {
        $mustMatch = false;
        $regex = substr($value, 1);
      }
    }

    foreach ($headers as $header)
    {
      if (false !== $regex)
      {
        if ($mustMatch)
        {
          if (preg_match($regex, $header))
          {
            $ok = true;
            $this->tester->pass(sprintf('email header "%s" matches "%s" (%s)', $key, $value, $current));
            break;
          }
        }
        else
        {
          if (preg_match($regex, $header))
          {
            $ok = true;
            $this->tester->fail(sprintf('email header "%s" does not match "%s" (%s)', $key, $value, $current));
            break;
          }
        }
      }
      else if ($header == $value)
      {
        $ok = true;
        $this->tester->pass(sprintf('email header "%s" is "%s" (%s)', $key, $value, $current));
        break;
      }
    }

    if (!$ok)
    {
      if (!$mustMatch)
      {
        $this->tester->pass(sprintf('email header "%s" matches "%s" (%s)', $key, $value, $current));
      }
      else
      {
        $this->tester->fail(sprintf('email header "%s" matches "%s" (%s)', $key, $value, $current));
      }
    }

    return $this->getObjectToReturn();
  }
}
