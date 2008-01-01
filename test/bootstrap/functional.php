<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// setup expected test environment (per check_configuration.php)
ini_set('magic_quotes_gpc', 'off');
ini_set('register_globals', 'off');
ini_set('session.auto_start', 'off');
ini_set('arg.output_separator', '&amp;');

ini_set('allow_url_fopen', 'on');

if (!isset($root_dir))
{
  $root_dir = realpath(dirname(__FILE__).sprintf('/../%s/fixtures/project', isset($type) ? $type : 'functional'));
}
define('SF_ROOT_DIR',    $root_dir);
define('SF_APP',         $app);
define('SF_ENVIRONMENT', 'test');
define('SF_DEBUG',       isset($debug) ? $debug : true);

// initialize symfony
require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

// remove all cache
sfToolkit::clearDirectory(sfConfig::get('sf_app_cache_dir'));

return true;
