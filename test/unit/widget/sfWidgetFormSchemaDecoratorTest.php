<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(9, new lime_output_color());

$w1 = new sfWidgetFormInput();
$w2 = new sfWidgetFormInput();
$ws = new sfWidgetFormSchema(array('w1' => $w1));

$w = new sfWidgetFormSchemaDecorator($ws, "<table>\n%content%</table>");

// ->render()
$t->diag('->render()');
$output = <<<EOF
<table>
<tr>
  <th><label for="w1">W1</label></th>
  <td><input type="text" name="w1" id="w1" /></td>
</tr>
</table>
EOF;
$t->is($w->render(null), $output, '->render() decorates the widget');

// implements ArrayAccess
$t->diag('implements ArrayAccess');
$w['w2'] = $w2;
$t->is($w->getFields(), array('w1' => $w1, 'w2' => $w2), 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');
$t->is($ws->getFields(), array('w1' => $w1), 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');

try
{
  $w['w1'] = 'string';
  $t->fail('sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');
}
catch (LogicException $e)
{
  $t->pass('sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');
}

$w = new sfWidgetFormSchemaDecorator($ws, "<table>\n%content%</table>");
$t->is(isset($w['w1']), true, 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');
$t->is(isset($w['w2']), false, 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');

$w = new sfWidgetFormSchemaDecorator($ws, "<table>\n%content%</table>");
$t->is($w['w1'], $w1, 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');
$t->is($w['w2'], null, 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');

$w = new sfWidgetFormSchemaDecorator($ws, "<table>\n%content%</table>");
unset($w['w1']);
$t->is($w['w1'], null, 'sfWidgetFormSchemaDecorator implements the ArrayAccess interface for the fields');
