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
  protected
    $cookies     = array(),
    $statusCode  = 200,
    $statusText  = 'OK',
    $statusTexts = array(),
    $headerOnly  = false,
    $headers     = array(),
    $metas       = array(),
    $httpMetas   = array(),
    $stylesheets = array(),
    $javascripts = array(),
    $slots       = array();

  /**
   * Initializes this sfWebResponse.
   *
   * @param  sfEventDispatcher  A sfEventDispatcher instance
   * @param  array              An array of parameters
   *
   * @return Boolean            true, if initialization completes successfully, otherwise false
   *
   * @throws <b>sfInitializationException</b> If an error occurs while initializing this sfResponse
   */
  public function initialize(sfEventDispatcher $dispatcher, $parameters = array())
  {
    parent::initialize($dispatcher, $parameters);

    $this->statusTexts = array(
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
   * Sets if the response consist of just HTTP headers.
   *
   * @param boolean
   */
  public function setHeaderOnly($value = true)
  {
    $this->headerOnly = (boolean) $value;
  }

  /**
   * Returns if the response must only consist of HTTP headers.
   *
   * @return boolean returns true if, false otherwise
   */
  public function isHeaderOnly()
  {
    return $this->headerOnly;
  }

  /**
   * Sets a cookie.
   *
   * @param string HTTP header name
   * @param string Value for the cookie
   * @param string Cookie expiration period
   * @param string Path
   * @param string Domain name
   * @param boolean If secure
   * @param boolean If uses only HTTP
   *
   * @throws <b>sfException</b> If fails to set the cookie
   */
  public function setCookie($name, $value, $expire = null, $path = '/', $domain = '', $secure = false, $httpOnly = false)
  {
    if ($expire !== null)
    {
      if (is_numeric($expire))
      {
        $expire = (int) $expire;
      }
      else
      {
        $expire = strtotime($expire);
        if ($expire === false || $expire == -1)
        {
          throw new sfException('Your expire parameter is not valid.');
        }
      }
    }

    $this->cookies[] = array(
      'name'     => $name,
      'value'    => $value,
      'expire'   => $expire,
      'path'     => $path,
      'domain'   => $domain,
      'secure'   => $secure ? true : false,
      'httpOnly' => $httpOnly,
    );
  }

  /**
   * Sets response status code.
   *
   * @param string HTTP status code
   * @param string HTTP status text
   *
   */
  public function setStatusCode($code, $name = null)
  {
    $this->statusCode = $code;
    $this->statusText = null !== $name ? $name : $this->statusTexts[$code];
  }

  /**
   * Retrieves status code for the current web response.
   *
   * @return string Status code
   */
  public function getStatusCode()
  {
    return $this->statusCode;
  }

  /**
   * Sets a HTTP header.
   *
   * @param string  HTTP header name
   * @param string  Value (if null, remove the HTTP header)
   * @param boolean Replace for the value
   *
   */
  public function setHttpHeader($name, $value, $replace = true)
  {
    $name = $this->normalizeHeaderName($name);

    if (is_null($value))
    {
      unset($this->headers[$name]);

      return;
    }

    if ('Content-Type' == $name)
    {
      if ($replace || !$this->getHttpHeader('Content-Type', null))
      {
        $this->setContentType($value);
      }

      return;
    }

    if (!$replace)
    {
      $current = isset($this->headers[$name]) ? $this->headers[$name] : '';
      $value = ($current ? $current.', ' : '').$value;
    }

    $this->headers[$name] = $value;
  }

  /**
   * Gets HTTP header current value.
   *
   * @return array
   */
  public function getHttpHeader($name, $default = null)
  {
    $name = $this->normalizeHeaderName($name);

    return isset($this->headers[$name]) ? $this->headers[$name] : $default;
  }

  /**
   * Has a HTTP header.
   *
   * @return boolean
   */
  public function hasHttpHeader($name)
  {
    return array_key_exists($this->normalizeHeaderName($name), $this->headers);
  }

  /**
   * Sets response content type.
   *
   * @param string Content type
   *
   */
  public function setContentType($value)
  {
    // add charset if needed (only on text content)
    if (false === stripos($value, 'charset') && (0 === stripos($value, 'text/') || strlen($value) - 3 === strripos($value, 'xml')))
    {
      $value .= '; charset='.$this->getParameter('charset');
    }

    $this->headers['Content-Type'] = $value;
  }

  /**
   * Gets response content type.
   *
   * @return array
   */
  public function getContentType()
  {
    return $this->getHttpHeader('Content-Type', 'text/html; charset='.$this->getParameter('charset'));
  }

  /**
   * Send HTTP headers and cookies.
   *
   */
  public function sendHttpHeaders()
  {
    if (sfConfig::get('sf_test'))
    {
      return;
    }

    // status
    $status = 'HTTP/1.1 '.$this->statusCode.' '.$this->statusText;
    header($status);

    if ($this->getParameter('logging'))
    {
      $this->dispatcher->notify(new sfEvent($this, 'application.log', array(sprintf('Send status "%s"', $status))));
    }

    // headers
    foreach ($this->headers as $name => $value)
    {
      header($name.': '.$value);

      if ($value != '' && $this->getParameter('logging'))
      {
        $this->dispatcher->notify(new sfEvent($this, 'application.log', array(sprintf('Send header "%s": "%s"', $name, $value))));
      }
    }

    // cookies
    foreach ($this->cookies as $cookie)
    {
      if (version_compare(phpversion(), '5.2', '>='))
      {
        setrawcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httpOnly']);
      }
      else
      {
        setrawcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure']);
      }

      if ($this->getParameter('logging'))
      {
        $this->dispatcher->notify(new sfEvent($this, 'application.log', array(sprintf('Send cookie "%s": "%s"', $cookie['name'], $cookie['value']))));
      }
    }
  }

  /**
   * Send content for the current web response.
   *
   */
  public function sendContent()
  {
    if (!$this->headerOnly)
    {
      parent::sendContent();
    }
  }

  /**
   * Sends the HTTP headers and the content.
   */
  public function send()
  {
    $this->sendHttpHeaders();
    $this->sendContent();
  }

  /**
   * Retrieves a normalized Header.
   *
   * @param string Header name
   *
   * @return string Normalized header
   */
  protected function normalizeHeaderName($name)
  {
    return preg_replace('/\-(.)/e', "'-'.strtoupper('\\1')", strtr(ucfirst(strtolower($name)), '_', '-'));
  }

  /**
   * Retrieves a formated date.
   *
   * @param string Timestamp
   * @param string Format type
   *
   * @return string Formated date
   */
  public function getDate($timestamp, $type = 'rfc1123')
  {
    $type = strtolower($type);

    if ($type == 'rfc1123')
    {
      return substr(gmdate('r', $timestamp), 0, -5).'GMT';
    }
    else if ($type == 'rfc1036')
    {
      return gmdate('l, d-M-y H:i:s ', $timestamp).'GMT';
    }
    else if ($type == 'asctime')
    {
      return gmdate('D M j H:i:s', $timestamp);
    }
    else
    {
      throw new sfParameterException('The second getDate() method parameter must be one of: rfc1123, rfc1036 or asctime.');
    }
  }

  /**
   * Adds vary to a http header.
   *
   * @param string HTTP header
   */
  public function addVaryHttpHeader($header)
  {
    $vary = $this->getHttpHeader('Vary');
    $currentHeaders = array();
    if ($vary)
    {
      $currentHeaders = split('/\s*,\s*/', $vary);
    }
    $header = $this->normalizeHeaderName($header);

    if (!in_array($header, $currentHeaders))
    {
      $currentHeaders[] = $header;
      $this->setHttpHeader('Vary', implode(', ', $currentHeaders));
    }
  }

  /**
   * Adds an control cache http header.
   *
   * @param string HTTP header
   * @param string Value for the http header
   */
  public function addCacheControlHttpHeader($name, $value = null)
  {
    $cacheControl = $this->getHttpHeader('Cache-Control');
    $currentHeaders = array();
    if ($cacheControl)
    {
      foreach (split('/\s*,\s*/', $cacheControl) as $tmp)
      {
        $tmp = explode('=', $tmp);
        $currentHeaders[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : null;
      }
    }
    $currentHeaders[strtr(strtolower($name), '_', '-')] = $value;

    $headers = array();
    foreach ($currentHeaders as $key => $value)
    {
      $headers[] = $key.(null !== $value ? '='.$value : '');
    }

    $this->setHttpHeader('Cache-Control', implode(', ', $headers));
  }

  /**
   * Retrieves meta headers for the current web response.
   *
   * @return string Meta headers
   */
  public function getHttpMetas()
  {
    return $this->httpMetas;
  }

  /**
   * Adds a HTTP meta header.
   *
   * @param string  Key to replace
   * @param string  HTTP meta header value (if null, remove the HTTP meta)
   * @param boolean Replace or not
   */
  public function addHttpMeta($key, $value, $replace = true)
  {
    $key = $this->normalizeHeaderName($key);

    // set HTTP header
    $this->setHttpHeader($key, $value, $replace);

    if (is_null($value))
    {
      unset($this->httpMetas[$key]);

      return;
    }

    if ('Content-Type' == $key)
    {
      $value = $this->getContentType();
    }
    elseif (!$replace)
    {
      $current = isset($this->httpMetas[$key]) ? $this->httpMetas[$key] : '';
      $value = ($current ? $current.', ' : '').$value;
    }

    $this->httpMetas[$key] = $value;
  }

  /**
   * Retrieves all meta headers.
   *
   * @return array List of meta headers
   */
  public function getMetas()
  {
    return $this->metas;
  }

  /**
   * Adds a meta header.
   *
   * @param string  Name of the header
   * @param string  Meta header value (if null, remove the meta)
   * @param boolean true if it's replaceable
   * @param boolean true for escaping the header
   */
  public function addMeta($key, $value, $replace = true, $escape = true)
  {
    $key = strtolower($key);

    if (is_null($value))
    {
      unset($this->metas[$key]);

      return;
    }

    // FIXME: If you use the i18n layer and escape the data here, it won't work
    // see include_metas() in AssetHelper
    if ($escape)
    {
      $value = htmlentities($value, ENT_QUOTES, $this->getParameter('charset'));
    }

    $current = isset($this->metas[$key]) ? $this->metas[$key] : null;
    if ($replace || !$current)
    {
      $this->metas[$key] = $value;
    }
  }

  /**
   * Retrieves title for the current web response.
   *
   * @return string Title
   */
  public function getTitle()
  {
    return isset($this->metas['title']) ? $this->metas['title'] : '';
  }

  /**
   * Sets title for the current web response.
   *
   * @param string Title name
   * @param boolean true, for escaping the title
   */
  public function setTitle($title, $escape = true)
  {
    $this->addMeta('title', $title, true, $escape);
  }

  /**
   * Retrieves stylesheets for the current web response.
   *
   * @param string  Position
   *
   * @return string Stylesheets
   */
  public function getStylesheets($position = '')
  {
    if ($position == 'ALL')
    {
      return $this->stylesheets;
    }

    return isset($this->stylesheets[$position]) ? $this->stylesheets[$position] : array();
  }

  /**
   * Adds an stylesheet to the current web response.
   *
   * @param string Stylesheet
   * @param string Position
   * @param string Stylesheet options
   */
  public function addStylesheet($css, $position = '', $options = array())
  {
    if (!isset($this->stylesheets[$position]))
    {
      $this->stylesheets[$position] = array();
    }

    $this->stylesheets[$position][$css] = $options;
  }

  /**
   * Retrieves javascript code from the current web response.
   *
   * @param string  Position
   *
   * @return string Javascript code
   */
  public function getJavascripts($position = '')
  {
    if ($position == 'ALL')
    {
      return $this->javascripts;
    }

    return isset($this->javascripts[$position]) ? $this->javascripts[$position] : array();
  }

  /**
   * Adds javascript code to the current web response.
   *
   * @param string Javascript code
   * @param string Position
   * @param string Javascript options
   */
  public function addJavascript($js, $position = '', $options = array())
  {
    if (!isset($this->javascripts[$position]))
    {
      $this->javascripts[$position] = array();
    }

    $this->javascripts[$position][$js] = $options;
  }

  /**
   * Retrieves slots from the current web response.
   *
   * @return string Javascript code
   */
  public function getSlots()
  {
    return $this->slots;
  }

  /**
   * Sets a slot content.
   *
   * @param string Slot name
   * @param string Content
   */
  public function setSlot($name, $content)
  {
    $this->slots[$name] = $content;
  }

  /**
   * Retrieves cookies from the current web response.
   *
   * @return array Cookies
   */
  public function getCookies()
  {
    $cookies = array();
    foreach ($this->cookies as $cookie)
    {
      $cookies[$cookie['name']] = $cookie;
    }

    return $cookies;
  }

  /**
   * Retrieves HTTP headers from the current web response.
   *
   * @return string HTTP headers
   */
  public function getHttpHeaders()
  {
    return $this->headers;
  }

  /**
   * Cleans HTTP headers from the current web response.
   */
  public function clearHttpHeaders()
  {
    $this->headers = array();
  }

  /**
   * Copies all properties from a given sfWebResponse object to the current one.
   *
   * @param sfWebResponse A sfWebResponse instance
   */
  public function mergeProperties(sfWebResponse $response)
  {
    $this->parameterHolder = clone $response->getParameterHolder();
    $this->headers         = $response->getHttpHeaders();
    $this->metas           = $response->getMetas();
    $this->httpMetas       = $response->getHttpMetas();
    $this->stylesheets     = $response->getStylesheets('ALL');
    $this->javascripts     = $response->getJavascripts('ALL');
    $this->slots           = $response->getSlots();
  }

  /**
   * Serializes the current instance.
   *
   * @return array Objects instance
   */
  public function serialize()
  {
    return serialize(array($this->content, $this->statusCode, $this->statusText, $this->parameterHolder, $this->cookies, $this->headerOnly, $this->headers, $this->metas, $this->httpMetas, $this->stylesheets, $this->javascripts, $this->slots));
  }

  /**
   * Unserializes a sfWebResponse instance.
   */
  public function unserialize($serialized)
  {
    $data = unserialize($serialized);

    $this->initialize(sfContext::hasInstance() ? sfContext::getInstance()->getEventDispatcher() : new sfEventDispatcher());

    list($this->content, $this->statusCode, $this->statusText, $this->parameterHolder, $this->cookies, $this->headerOnly, $this->headers, $this->metas, $this->httpMetas, $this->stylesheets, $this->javascripts, $this->slots) = $data;
  }
}
