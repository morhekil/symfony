<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$app = 'frontend';
if (!include(dirname(__FILE__).'/../bootstrap/functional.php'))
{
  return;
}

$b = new sfTestBrowser();

$b->
  get('/format_test.js')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'index')->
  isResponseHeader('content-type', 'application/javascript')
;
$b->test()->unlike($b->getResponse()->getContent(), '/<body>/', 'response content is ok');
$b->test()->like($b->getResponse()->getContent(), '/Some js headers/', 'response content is ok');
$b->test()->like($b->getResponse()->getContent(), '/This is a js file/', 'response content is ok');

$b->
  get('/format_test.css')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'index')->
  isResponseHeader('content-type', 'text/css; charset=utf-8')
;
$b->test()->is($b->getResponse()->getContent(), 'This is a css file', 'response content is ok');

$b->
  get('/format_test')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'index')->
  isResponseHeader('content-type', 'text/html; charset=utf-8')->
  checkResponseElement('body #content', 'This is an HTML file')
;

$b->
  get('/format_test.xml')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'index')->
  isResponseHeader('content-type', 'text/xml; charset=utf-8')->
  checkResponseElement('sentences sentence:first', 'This is a XML file')
;

$b->
  get('/format_test.foo')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'index')->
  isResponseHeader('content-type', 'text/html; charset=utf-8')->
  isResponseHeader('x-foo', 'true')->
  checkResponseElement('body #content', 'This is an HTML file')
;

$b->
  setHttpHeader('Accept', 'application/javascript')->
  get('/format/jsWithAccept')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'jsWithAccept')->
  isResponseHeader('content-type', 'application/javascript')
;
$b->test()->like($b->getResponse()->getContent(), '/This is a js file/', 'response content is ok');

$b->
  get('/format/js')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'js')->
  isResponseHeader('content-type', 'application/javascript')
;
$b->test()->is($b->getResponse()->getContent(), 'A js file', 'response content is ok');

$b->getContext()->getEventDispatcher()->connect('request.filter_parameters', 'filter_parameters');
$b->getContext()->getEventDispatcher()->connect('view.configure_format', 'configure_iphone_format');
$b->
  setHttpHeader('User-Agent', 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543a Safari/419.3')->
  get('/format/forTheIPhone')->
  isStatusCode(200)->
  isRequestParameter('module', 'format')->
  isRequestParameter('action', 'forTheIPhone')->
  isResponseHeader('content-type', 'text/html; charset=utf-8')->
  checkResponseElement('#content', 'This is an HTML file for the iPhone')->
  checkResponseElement('link[href*="iphone.css"]')
;

function filter_parameters(sfEvent $event, $parameters)
{
  if (false !== stripos($event->getSubject()->getHttpHeader('user-agent'), 'iPhone'))
  {
    $event->getSubject()->setRequestFormat('iphone');
  }

  return $parameters;
}

function configure_iphone_format(sfEvent $event)
{
  if ('iphone' == $event['format'])
  {
    $event['response']->addStylesheet('iphone.css');

    $event->getSubject()->setDecorator(true);
  }
}