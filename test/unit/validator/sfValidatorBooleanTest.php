<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(16, new lime_output_color());

$v = new sfValidatorBoolean();

// ->getErrorCodes()
$t->diag('->getErrorCodes()');
$t->is($v->getErrorCodes(), array('required', 'invalid'), '->getErrorCodes() returns all possible error codes');

// ->clean()
$t->diag('->clean()');

// empty
$t->diag('empty');
$v->setOption('required', false);
$t->ok($v->clean(null) === null, '->clean() returns null if no value is given');
$v->setOption('empty_value', true);
$t->ok($v->clean(null) === true, '->clean() returns the value of the empty_value option if no value is given');

// true values
$t->diag('true values');
foreach ($v->getOption('true_values') as $true_value)
{
  $t->is($v->clean($true_value), true, '->clean() returns true if the value is in the true_values option');
}

// false values
$t->diag('false values');
foreach ($v->getOption('false_values') as $false_value)
{
  $t->is($v->clean($false_value), false, '->clean() returns false if the value is in the false_values option');
}

try
{
  $v->clean('astring');
  $t->fail('->clean() throws an error if the input value is not a true or a false value');
}
catch (sfValidatorError $e)
{
  $t->pass('->clean() throws an error if the input value is not a true or a false value');
}
