<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class is the Propel implementation of sfData.
 *
 * It interacts with the data source and loads data.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelData extends sfData
{
  protected
    $deletedClasses = array(),
    $con            = null;

  /**
   * Loads data from a file or directory into a Propel data source
   *
   * @param mixed A file or directory path
   * @param string The Propel connection name, default 'propel'
   *
   * @throws Exception If the database throws an error, rollback transaction and rethrows exception
   */
  public function loadData($directory_or_file = null, $connectionName = 'propel')
  {
    $fixture_files = $this->getFiles($directory_or_file);

    // load map classes
    $this->loadMapBuilders();
    $this->dbMap = Propel::getDatabaseMap($connectionName);

    // wrap all database operations in a single transaction
    $this->con = Propel::getConnection($connectionName);
    try
    {
      $this->con->begin();

      $this->doDeleteCurrentData($fixture_files);

      $this->doLoadData($fixture_files);

      $this->con->commit();
    }
    catch (Exception $e)
    {
      $this->con->rollback();
      throw $e;
    }
  }

  /**
   * Implements the abstract loadDataFromArray method and loads the data using the generated data model.
   *
   * @param array The data to be loaded into the data source
   *
   * @throws Exception If data is unnamed.
   * @throws sfException If an object defined in the model does not exist in the data
   * @throws sfException If a column that does not exist is referenced
   */
  public function loadDataFromArray($data)
  {
    if ($data === null)
    {
      // no data
      return;
    }

    foreach ($data as $class => $datas)
    {
      $class = trim($class);

      $peer_class = $class.'Peer';

      $tableMap = $this->dbMap->getTable(constant($peer_class.'::TABLE_NAME'));

      $column_names = call_user_func_array(array($peer_class, 'getFieldNames'), array(BasePeer::TYPE_FIELDNAME));

      // iterate through datas for this class
      // might have been empty just for force a table to be emptied on import
      if (!is_array($datas))
      {
        continue;
      }

      foreach ($datas as $key => $data)
      {
        // create a new entry in the database
        $obj = new $class();

        if (!$obj instanceof BaseObject)
        {
          throw new Exception(sprintf('The class "%s" is not a Propel class. This probably means there is already a class named "%s" somewhere in symfony or in your project.', $class, $class));
        }

        if (!is_array($data))
        {
          throw new Exception(sprintf('You must give a name for each fixture data entry (class %s)', $class));
        }

        foreach ($data as $name => $value)
        {
          // will need to be updated for Propel 1.3
          if (is_array($value) && 's' == substr($name, -1))
          {
            // many to many relationship
            $this->loadMany2Many($obj, substr($name, 0, -1), $value);

            continue;
          }

          $isARealColumn = true;
          try
          {
            $column = $tableMap->getColumn($name);
          }
          catch (PropelException $e)
          {
            $isARealColumn = false;
          }

          // foreign key?
          if ($isARealColumn)
          {
            if ($column->isForeignKey() && !is_null($value))
            {
              $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
              if (!isset($this->object_references[$relatedTable->getPhpName().'_'.$value]))
              {
                throw new sfException(sprintf('The object "%s" from class "%s" is not defined in your data file.', $value, $relatedTable->getPhpName()));
              }
              $value = $this->object_references[$relatedTable->getPhpName().'_'.$value]->getPrimaryKey();
            }
          }

          if (false !== $pos = array_search($name, $column_names))
          {
            $obj->setByPosition($pos, $value);
          }
          else if (is_callable(array($obj, $method = 'set'.sfInflector::camelize($name))))
          {
            $obj->$method($value);
          }
          else
          {
            throw new sfException(sprintf('Column "%s" does not exist for class "%s"', $name, $class));
          }
        }
        $obj->save($this->con);

        // save the object for future reference
        if (method_exists($obj, 'getPrimaryKey'))
        {
          $this->object_references[$class.'_'.$key] = $obj;
        }
      }
    }
  }

  /**
   * Loads many to many objects.
   *
   * @param BaseObject A Propel object
   * @param string     The middle table name
   * @param array      An array of values
   */
  protected function loadMany2Many($obj, $middleTableName, $values)
  {
    $middleTable = $this->dbMap->getTable($middleTableName);
    $middleClass = $middleTable->getPhpName();
    foreach ($middleTable->getColumns()  as $column)
    {
      if ($column->isPrimaryKey() && $column->isForeignKey() && constant(get_class($obj).'Peer::TABLE_NAME') != $column->getRelatedTableName())
      {
        $relatedClass = $this->dbMap->getTable($column->getRelatedTableName())->getPhpName();
        break;
      }
    }

    if (!isset($relatedClass))
    {
      throw new sfException(sprintf('Unable to find the many-to-many relationship for object "%s"', get_class($obj)));
    }

    $setter = 'set'.get_class($obj);
    $relatedSetter = 'set'.$relatedClass;

    foreach ($values as $value)
    {
      if (!isset($this->object_references[$relatedClass.'_'.$value]))
      {
        throw new sfException(sprintf('The object "%s" from class "%s" is not defined in your data file.', $value, $relatedClass));
      }

      $middle = new $middleClass();
      $middle->$setter($obj);
      $middle->$relatedSetter($this->object_references[$relatedClass.'_'.$value]);
      $middle->save();
    }
  }

  /**
   * Clears existing data from the data source by reading the fixture files
   * and deleting the existing data for only those classes that are mentioned
   * in the fixtures.
   *
   * @param array The list of YAML files.
   *
   * @throws sfException If a class mentioned in a fixture can not be found
   */
  protected function doDeleteCurrentData($fixture_files)
  {
    // delete all current datas in database
    if (!$this->deleteCurrentData)
    {
      return;
    }

    rsort($fixture_files);
    foreach ($fixture_files as $fixture_file)
    {
      $data = sfYaml::load($fixture_file);

      if ($data === null)
      {
        // no data
        continue;
      }

      $classes = array_keys($data);
      krsort($classes);
      foreach ($classes as $class)
      {
        $class = trim($class);
        if (in_array($class, $this->deletedClasses))
        {
          continue;
        }

        $peer_class = $class.'Peer';

        if (!$classPath = sfAutoload::getInstance()->getClassPath($peer_class))
        {
          throw new sfException(sprintf('Unable to find path for class "%s".', $peer_class));
        }

        require_once($classPath);

        call_user_func(array($peer_class, 'doDeleteAll'), $this->con);

        $this->deletedClasses[] = $class;
      }
    }
  }

  /**
   * Loads all map builders.
   *
   * @throws sfException If the class cannot be found
   */
  protected function loadMapBuilders()
  {
    $files = sfFinder::type('file')->name('*MapBuilder.php')->in(sfLoader::getModelDirs());
    foreach ($files as $file)
    {
      $mapBuilderClass = basename($file, '.php');
      $map = new $mapBuilderClass();
      $map->doBuild();
    }
  }

  /**
   * Dumps data to fixture from one or more tables.
   *
   * @param string directory or file to dump to
   * @param mixed  name or names of tables to dump (or all to dump all tables)
   * @param string connection name
   */
  public function dumpData($directory_or_file = null, $tables = 'all', $connectionName = 'propel')
  {
    $sameFile = true;
    if (is_dir($directory_or_file))
    {
      // multi files
      $sameFile = false;
    }
    else
    {
      // same file
      // delete file
    }

    $this->loadMapBuilders();
    $this->con = Propel::getConnection($connectionName);
    $this->dbMap = Propel::getDatabaseMap($connectionName);

    // get tables
    if ('all' === $tables || is_null($tables))
    {
      $tables = array();
      foreach ($this->dbMap->getTables() as $table)
      {
        $tables[] = $table->getPhpName();
      }
    }
    else if (!is_array($tables))
    {
      $tables = array($tables);
    }

    $dumpData = array();

    $tables = $this->fixOrderingOfForeignKeyData($tables);
    foreach ($tables as $tableName)
    {
      $tableMap = $this->dbMap->getTable(constant($tableName.'Peer::TABLE_NAME'));
      $hasParent = false;
      $haveParents = false;
      $fixColumn = null;
      foreach ($tableMap->getColumns() as $column)
      {
        $col = strtolower($column->getColumnName());
        if ($column->isForeignKey())
        {
          $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
          if ($tableName === $relatedTable->getPhpName())
          {
            if ($hasParent)
            {
              $haveParents = true;
            }
            else
            {
              $fixColumn = $column;
              $hasParent = true;
            }
          }
        }
      }

      if ($haveParents)
      {
        // unable to dump tables having multi-recursive references
        continue;
      }

      // get db info
      $resultsSets = array();
      if ($hasParent)
      {
        $resultsSets = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $fixColumn);
      }
      else
      {
        $resultsSets[] = $this->con->executeQuery('SELECT * FROM '.constant($tableName.'Peer::TABLE_NAME'));
      }

      foreach ($resultsSets as $rs)
      {
        if($rs->getRecordCount() > 0 && !isset($dumpData[$tableName])){
          $dumpData[$tableName] = array();
        }

        while ($rs->next())
        {
          $pk = $tableName;
          $values = array();
          $primaryKeys = array();
          $foreignKeys = array();

          foreach ($tableMap->getColumns() as $column)
          {
            $col = strtolower($column->getColumnName());
            $isPrimaryKey = $column->isPrimaryKey();

            if (is_null($rs->get($col)))
            {
              continue;
            }

            if ($isPrimaryKey)
            {
              $value = $rs->get($col);
              $pk .= '_'.$value;
              $primaryKeys[$col] = $value;
            }

            if ($column->isForeignKey())
            {
              $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
              if ($isPrimaryKey)
              {
                $foreignKeys[$col] = $rs->get($col);
                $primaryKeys[$col] = $relatedTable->getPhpName().'_'.$rs->get($col);
              }
              else
              {
                $values[$col] = $relatedTable->getPhpName().'_'.$rs->get($col);
              }
            }
            elseif (!$isPrimaryKey || ($isPrimaryKey && !$tableMap->isUseIdGenerator()))
            {
              // We did not want auto incremented primary keys
              $values[$col] = $rs->get($col);
            }
          }

          if (count($primaryKeys) > 1 || (count($primaryKeys) > 0 && count($foreignKeys) > 0))
          {
            $values = array_merge($primaryKeys, $values);
          }

          $dumpData[$tableName][$pk] = $values;
        }
      }
    }

    // save to file(s)
    if ($sameFile)
    {
      file_put_contents($directory_or_file, Spyc::YAMLDump($dumpData));
    }
    else
    {
      $i = 0;
      foreach ($tables as $tableName)
      {
        if (!isset($dumpData[$tableName]))
        {
          continue;
        }

        file_put_contents(sprintf("%s/%03d-%s.yml", $directory_or_file, ++$i, $tableName), Spyc::YAMLDump(array($tableName => $dumpData[$tableName])));
      }
    }
  }

  /**
   * Fixes the ordering of foreign key data, by outputting data a foreign key depends on before the table with the foreign key.
   *
   * @param array The array with the class names.
   */
  public function fixOrderingOfForeignKeyData($classes)
  {
    // reordering classes to take foreign keys into account
    for ($i = 0, $count = count($classes); $i < $count; $i++)
    {
      $class = $classes[$i];
      $tableMap = $this->dbMap->getTable(constant($class.'Peer::TABLE_NAME'));
      foreach ($tableMap->getColumns() as $column)
      {
        if ($column->isForeignKey())
        {
          $relatedTable = $this->dbMap->getTable($column->getRelatedTableName());
          $relatedTablePos = array_search($relatedTable->getPhpName(), $classes);

          // check if relatedTable is after the current table
          if ($relatedTablePos > $i)
          {
            // move related table 1 position before current table
            $classes = array_merge(
              array_slice($classes, 0, $i),
              array($classes[$relatedTablePos]),
              array_slice($classes, $i, $relatedTablePos - $i),
              array_slice($classes, $relatedTablePos + 1)
            );

            // we have moved a table, so let's see if we are done
            return $this->fixOrderingOfForeignKeyData($classes);
          }
        }
      }
    }

    return $classes;
  }
  
  protected function fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $column, $in = null)
  {
    $rs = $this->con->executeQuery(sprintf('SELECT * FROM %s WHERE %s %s',
      constant($tableName.'Peer::TABLE_NAME'),
      strtolower($column->getColumnName()),
      is_null($in) ? 'IS NULL' : 'IN ('.$in.')'
    ));
    $in = array();
    while ($rs->next())
    {
      $in[] = "'".$rs->get(strtolower($column->getRelatedColumnName()))."'";
    }

    if ($in = implode(', ', $in))
    {
      $rs->seek(0);
      $resultsSets[] = $rs;
      $resultsSets = $this->fixOrderingOfForeignKeyDataInSameTable($resultsSets, $tableName, $column, $in);
    }

    return $resultsSets;
  }
}
