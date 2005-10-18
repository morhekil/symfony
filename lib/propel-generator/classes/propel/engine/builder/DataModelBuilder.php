<?php

/*
 *  $Id: DataModelBuilder.php 186 2005-09-08 13:33:09Z hans $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */


/**
 * This is the base class for any builder class that is using the data model.
 * 
 * This could be extended by classes that build SQL DDL, PHP classes, configuration
 * files, input forms, etc.
 * 
 * This class has a static method to return the correct builder subclass identified by 
 * a given key.  Note that in order for this factory method to work, the properties have to have
 * been loaded first.  Usage should look something like this (from within a AbstractProelDataModelTask subclass):
 * 
 * <code>
 * DataModelBuilder::setBuildProperties($this->getPropelProperties());
 * $builder = DataModelBuilder::builderFactory($table, 'peer');
 * // $builder (by default) instanceof PHP5ComplexPeerBuilder
 * </code>
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder
 */
abstract class DataModelBuilder {
	
	// --------------------------------------------------------------
	// Static properties & methods
	// --------------------------------------------------------------
	
	/**
	 * Build properties (after they've been transformed from "propel.some.name" => "someName").
	 * @var array string[]
	 */
	private static $buildProperties = array();
	
	/**
	 * Sets the [name transformed] build properties to use.
	 * @param array Property values keyed by [transformed] prop names.
	 */
	public static function setBuildProperties($props)
	{
		self::$buildProperties = $props;
	}
	
	/**
	 * Get a specific [name transformed] build property.
	 * @param string $name
	 * @return string
	 */
	public static function getBuildProperty($name)
	{
		return isset(self::$buildProperties[$name]) ? self::$buildProperties[$name] : null;
	}
	
	/**
	 * Factory method to load a new builder instance based on specified type.
	 * @param Table $table
	 * @param $type
	 * @throws BuildException if specified class cannot be found / loaded.
	 */
	public static function builderFactory(Table $table, $type)
	{
		if (empty(self::$buildProperties)) {
		    throw new BuildException("Cannot call builderFactory() method when no build properties have been loaded (hint: Did you call DataModelBuilder::setBuildProperties(\$props) first?)");
		}
		$propname = 'builder' . ucfirst(strtolower($type)) . 'Class';
		$classpath = self::getBuildProperty($propname);
		if (empty($classpath)) {
			throw new BuildException("Unable to find class path for '$propname' property.");
		}
		$classname = Phing::import($classpath);
		return new $classname($table);
	}
	
	/**
     * Utility function to build a path for use in include()/require() statement.
     * 
     * Supports two function signatures:
     * (1) getFilePath($dotPathClass);
     * (2) getFilePath($dotPathPrefix, $className);
     * 
     * @param string $path dot-path to class or to package prefix.
     * @param string $classname class name
     * @return string
     */
    public static function getFilePath($path, $classname = null, $extension = '.php')
    {
        $path = strtr(ltrim($path, '.'), '.', '/');
        if ($classname !== null) {
            if ($path !== "") { $path .= '/'; }
            return $path . $classname . $extension;
        } else {
            return $path . $extension;
        }
    }
	
	// --------------------------------------------------------------
	// Non-static properties & methods inherited by subclasses
	// --------------------------------------------------------------

	/**
	 * The current table.
	 * @var Table
	 */
	private $table;	

	/**
	 * Creates new instance of DataModelBuilder subclass.
	 * @param Table $table The Table which we are using to build [OM, DDL, etc.].
	 */
	public function __construct(Table $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Returns the Platform class for this table (database).
	 * @return Platform
	 */
	protected function getPlatform()
	{
		return $this->getTable()->getDatabase()->getPlatform();
	}
	
	/**
	 * Returns the database for current table.
	 * @return Database
	 */
	protected function getDatabase()
	{
		return $this->getTable()->getDatabase();
	}
	
	/**
	 * Returns the current Table object.
	 * @return Table
	 */
	protected function getTable()
	{
		return $this->table;
	}	
	
}