<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWebResponse class.
 *
 * This class manages web reponses. It supports cookies and headers management.
 * 
 * @package    symfony
 * @subpackage response
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfWebResponse extends sfResponse
{
  private
    $cookies    = array(),
    $headers    = array(),
    $status     = 'HTTP/1.0 200 OK',
    $statusText = array();

  /**
   * Initialize this sfResponse.
   *
   * @param sfContext A sfContext instance.
   *
   * @return bool true, if initialization completes successfully, otherwise false.
   *
   * @throws <b>sfInitializationException</b> If an error occurs while initializing this Response.
   */
  public function initialize ($context, $parameters = array())
  {
    parent::initialize($context, $parameters);

    $this->headers['Content-Type'] = 'text/html';

    $this->statusText = array(
      '100' => 'Continue',
      '101' => 'Switching Protocols',
      '200' => 'OK',
      '201' => 'Created',
      '202' => 'Accepted',
      '203' => 'Non-Authoritative Information',
      '204' => 'No Content',
      '205' => 'Reset Content',
      '206' => 'Partial Content',
      '300' => 'Multiple Choices',
      '301' => 'Moved Permanently',
      '302' => 'Found',
      '303' => 'See Other',
      '304' => 'Not Modified',
      '305' => 'Use Proxy',
      '306' => '(Unused)',
      '307' => 'Temporary Redirect',
      '400' => 'Bad Request',
      '401' => 'Unauthorized',
      '402' => 'Payment Required',
      '403' => 'Forbidden',
      '404' => 'Not Found',
      '405' => 'Method Not Allowed',
      '406' => 'Not Acceptable',
      '407' => 'Proxy Authentication Required',
      '408' => 'Request Timeout',
      '409' => 'Conflict',
      '410' => 'Gone',
      '411' => 'Length Required',
      '412' => 'Precondition Failed',
      '413' => 'Request Entity Too Large',
      '414' => 'Request-URI Too Long',
      '415' => 'Unsupported Media Type',
      '416' => 'Requested Range Not Satisfiable',
      '417' => 'Expectation Failed',
      '500' => 'Internal Server Error',
      '501' => 'Not Implemented',
      '502' => 'Bad Gateway',
      '503' => 'Service Unavailable',
      '504' => 'Gateway Timeout',
      '505' => 'HTTP Version Not Supported',
    );
  }

  /**
   * Set a cookie.
   *
   * @param string HTTP header name
   * @param string value
   *
   * @return void
   */
  public function setCookie ($name, $value, $expire = '', $path = '', $domain = '', $secure = 0)
  {
    $this->cookies[] = array(
      'name'   => $name,
      'value'  => $value,
      'expire' => $expire,
      'path'   => $path,
      'domain' => $domain,
      'secure' => $secure,
    );
  }

  /**
   * Set response status code.
   *
   * @param string HTTP status code
   * @param string
   *
   * @return void
   */
  public function setStatus ($code, $name = null)
  {
    $this->status = 'HTTP/1.0 '.$code.' '.($name ? $name : $this->statusText[$code]);
  }

  /**
   * Set a HTTP header.
   *
   * @param string HTTP header name
   * @param string value
   *
   * @return void
   */
  public function setHeader ($name, $value, $replace = true)
  {
    $name = $this->normalizeHeaderName($name);

    if (!isset($this->headers[$name]) || $replace)
    {
      $this->headers[$name] = array();
    }

    $this->headers[$name][] = $value;
  }

  /**
   * Get HTTP header current value.
   *
   * @return array
   */
  public function getHeader ($name, $defaultValue = null)
  {
    $retval = $defaultValue;

    if (isset($this->headers[$this->normalizeHeaderName($name)]))
    {
      $retval = $this->headers[$this->normalizeHeaderName($name)];
    }

    return $retval;
  }

  /**
   * Set response content type.
   *
   * @param string value
   *
   * @return void
   */
  public function setContentType ($value)
  {
    $this->headers['Content-Type'] = $value;
  }

  /**
   * Get response content type.
   *
   * @return array
   */
  public function getContentType ()
  {
    return $this->headers['Content-Type'];
  }

  /**
   * Has a HTTP header.
   *
   * @return boolean
   */
  public function hasHeader ($name)
  {
    return isset($this->headers[$this->normalizeHeaderName($name)]);
  }

  /**
   * Send HTTP headers and cookies.
   *
   * @return void
   */
  public function sendHeaders ()
  {
    // status
    header($this->status);

    if (sfConfig::get('sf_logging_active'))
    {
      $this->getContext()->getLogger()->info('{sfResponse} send status "'.$this->status.'"');
    }

    // set headers from HTTP meta
    foreach ($this->getContext()->getRequest()->getAttributeHolder()->getAll('helper/asset/auto/httpmeta') as $name => $value)
    {
      $this->setHeader($name, $value);
    }

    // headers
    foreach ($this->headers as $name => $values)
    {
      foreach ($values as $value)
      {
        header($name.': '.$value);

        if (sfConfig::get('sf_logging_active'))
        {
          $this->getContext()->getLogger()->info('{sfResponse} send header "'.$name.'": "'.$value.'"');
        }
      }
    }

    // cookies
    foreach ($this->cookies as $cookie)
    {
      setrawcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure']);

      if (sfConfig::get('sf_logging_active'))
      {
        $this->getContext()->getLogger()->info('{sfResponse} send cookie "'.$cookie['name'].'": "'.$cookie['value'].'"');
      }
    }
  }

  private function normalizeHeaderName($name)
  {
    return preg_replace('/\-(.)/e', "'-'.strtoupper('\\1')", strtr(ucfirst(strtolower($name)), '_', '-'));
  }

  /**
   * Execute the shutdown procedure.
   *
   * @return void
   */
  public function shutdown ()
  {
  }
}

?>