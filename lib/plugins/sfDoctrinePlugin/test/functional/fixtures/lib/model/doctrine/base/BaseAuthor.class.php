<?php

/**
 * BaseAuthor
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string $name
 * @property Doctrine_Collection $Articles
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
abstract class BaseAuthor extends myDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('author');
        $this->hasColumn('name', 'string', 255, array('type' => 'string', 'length' => '255'));
    }

    public function setUp()
    {
        $this->hasMany('Article as Articles', array('local' => 'id',
                                                    'foreign' => 'author_id'));
    }
}