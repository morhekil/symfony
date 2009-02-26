<?php

/**
 * BaseProfile
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $user_id
 * @property string $first_name
 * @property string $last_name
 * @property User $User
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
abstract class BaseProfile extends myDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('profile');
        $this->hasColumn('user_id', 'integer', null, array('type' => 'integer'));
        $this->hasColumn('first_name', 'string', 255, array('type' => 'string', 'length' => '255'));
        $this->hasColumn('last_name', 'string', 255, array('type' => 'string', 'length' => '255'));
    }

    public function setUp()
    {
        $this->hasOne('User', array('local' => 'user_id',
                                    'foreign' => 'id'));
    }
}