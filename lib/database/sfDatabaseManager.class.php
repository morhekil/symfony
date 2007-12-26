<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfDatabaseManager allows you to setup your database connectivity before the
 * request is handled. This eliminates the need for a filter to manage database
 * connections.
 *
 * @package    symfony
 * @subpackage database
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */
class sfDatabaseManager
{
  protected
    $databases = array();

  /**
   * Class constructor.
   *
   * @see initialize()
   */
  public function __construct($options = array())
  {
    $this->initialize();

    if (isset($options['auto_shutdown']) && $options['auto_shutdown'])
    {
      register_shutdown_function(array($this, 'shutdown'));
    }
  }

  /**
   * Initializes this sfDatabaseManager object
   *
   * @return bool true, if initialization completes successfully, otherwise false
   *
   * @throws <b>sfInitializationException</b> If an error occurs while initializing this sfDatabaseManager object
   */
  public function initialize()
  {
    $this->loadConfiguration();
  }

  /**
   * Loads database configuration.
   */
  public function loadConfiguration()
  {
    require(sfConfigCache::getInstance()->checkConfig(sfConfig::get('sf_app_config_dir_name').'/databases.yml'));
  }

  /**
   * Retrieves the database connection associated with this sfDatabase implementation.
   *
   * @param string A database name
   *
   * @return mixed A Database instance
   *
   * @throws <b>sfDatabaseException</b> If the requested database name does not exist
   */
  public function getDatabase($name = 'default')
  {
    if (isset($this->databases[$name]))
    {
      return $this->databases[$name];
    }

    // nonexistent database name
    throw new sfDatabaseException(sprintf('Database "%s" does not exist.', $name));
  }

  /**
   * Returns the names of all database connections.
   *
   * @return array An array containing all database connection names
   */
  public function getNames()
  {
    return array_keys($this->databases);
  }

  /**
   * Executes the shutdown procedure
   *
   * @return void
   *
   * @throws <b>sfDatabaseException</b> If an error occurs while shutting down this DatabaseManager
   */
  public function shutdown()
  {
    // loop through databases and shutdown connections
    foreach ($this->databases as $database)
    {
      $database->shutdown();
    }
  }
}
