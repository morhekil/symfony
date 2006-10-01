<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(54, new lime_output_color());

// ::stringToArray()
$t->diag('::stringToArray()');
$tests = array(
  'foo=bar' => array('foo' => 'bar'),
  'foo1=bar1 foo=bar   ' => array('foo1' => 'bar1', 'foo' => 'bar'),
  'foo1="bar1 foo1"' => array('foo1' => 'bar1 foo1'),
  'foo1="bar1 foo1" foo=bar' => array('foo1' => 'bar1 foo1', 'foo' => 'bar'),
  'foo1 = "bar1=foo1" foo=bar' => array('foo1' => 'bar1=foo1', 'foo' => 'bar'),
  'foo1= \'bar1 foo1\'    foo  =     bar' => array('foo1' => 'bar1 foo1', 'foo' => 'bar'),
  'foo1=\'bar1=foo1\' foo = bar' => array('foo1' => 'bar1=foo1', 'foo' => 'bar'),
  'foo1=  bar1 foo1 foo=bar' => array('foo1' => 'bar1 foo1', 'foo' => 'bar'),
  'foo1="l\'autre" foo=bar' => array('foo1' => 'l\'autre', 'foo' => 'bar'),
  'foo1="l"autre" foo=bar' => array('foo1' => 'l"autre', 'foo' => 'bar'),
  'foo_1=bar_1' => array('foo_1' => 'bar_1'),
);

foreach ($tests as $string => $attributes)
{
  $t->is(sfToolkit::stringToArray($string), $attributes, '->stringToArray()');
}

// ::isUTF8()
$t->diag('::isUTF8()');
$t->is('été', true, '::isUTF8() returns true if the parameter is an UTF-8 encoded string');
$t->is(sfToolkit::isUTF8('AZERTYazerty1234-_'), true, '::isUTF8() returns true if the parameter is an UTF-8 encoded string');
$t->is(sfToolkit::isUTF8('AZERTYazerty1234-_'.chr(1)), false, '::isUTF8() returns false if the parameter is not an UTF-8 encoded string');

// ::literalize()
$t->diag('::literalize()');
foreach (array('true', 'on', '+', 'yes') as $param)
{
  $t->is(sfToolkit::literalize($param), true, sprintf('::literalize() returns true with "%s"', $param));
  if (strtoupper($param) != $param)
  {
    $t->is(sfToolkit::literalize(strtoupper($param)), true, sprintf('::literalize() returns true with "%s"', strtoupper($param)));
  }
  $t->is(sfToolkit::literalize(' '.$param.' '), true, sprintf('::literalize() returns true with "%s"', ' '.$param.' '));
}

foreach (array('false', 'off', '-', 'no') as $param)
{
  $t->is(sfToolkit::literalize($param), false, sprintf('::literalize() returns false with "%s"', $param));
  if (strtoupper($param) != $param)
  {
    $t->is(sfToolkit::literalize(strtoupper($param)), false, sprintf('::literalize() returns false with "%s"', strtoupper($param)));
  }
  $t->is(sfToolkit::literalize(' '.$param.' '), false, sprintf('::literalize() returns false with "%s"', ' '.$param.' '));
}

foreach (array('null', '~', '') as $param)
{
  $t->is(sfToolkit::literalize($param), null, sprintf('::literalize() returns null with "%s"', $param));
  if (strtoupper($param) != $param)
  {
    $t->is(sfToolkit::literalize(strtoupper($param)), null, sprintf('::literalize() returns null with "%s"', strtoupper($param)));
  }
  $t->is(sfToolkit::literalize(' '.$param.' '), null, sprintf('::literalize() returns null with "%s"', ' '.$param.' '));
}

// ::replaceConstants()
$t->diag('::replaceConstants()');
sfConfig::set('foo', 'bar');
$t->is(sfToolkit::replaceConstants('my value with a %foo% constant'), 'my value with a bar constant', '::replaceConstantsCallback() replaces constants enclosed in %');
$t->is(sfToolkit::replaceConstants('%Y/%m/%d %H:%M'), '%Y/%m/%d %H:%M', '::replaceConstantsCallback() does not replace unknown constants');

// ::isPathAbsolute()
$t->diag('::isPathAbsolute()');
$t->is(sfToolkit::isPathAbsolute('/test'), true, '::isPathAbsolute() returns true if path is absolute');
$t->is(sfToolkit::isPathAbsolute('\\test'), true, '::isPathAbsolute() returns true if path is absolute');
$t->is(sfToolkit::isPathAbsolute('C:\\test'), true, '::isPathAbsolute() returns true if path is absolute');
$t->is(sfToolkit::isPathAbsolute('d:/test'), true, '::isPathAbsolute() returns true if path is absolute');
$t->is(sfToolkit::isPathAbsolute('test'), false, '::isPathAbsolute() returns false if path is relative');
$t->is(sfToolkit::isPathAbsolute('../test'), false, '::isPathAbsolute() returns false if path is relative');
$t->is(sfToolkit::isPathAbsolute('..\\test'), false, '::isPathAbsolute() returns false if path is relative');

// ::stripComments()
$t->diag('::isPathAbsolute()');

$php = <<<EOF
<?php

# A perl like comment
// Another comment
/* A very long
comment
on several lines
*/

\$i = 1; // A comment on a PHP line
EOF;

$stripped_php = <<<EOF
<?php



\$i = 1; 
EOF;

$t->is(sfToolkit::stripComments($php), $stripped_php, '::stripComments() strip all comments from a php string');
sfConfig::set('sf_strip_comments', false);
$t->is(sfToolkit::stripComments($php), $php, '::stripComments() do nothing if "sf_strip_comments" is false');
