<?php

require_once ##SYMFONY_CORE_AUTOLOAD##;
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    // for compatibility / remove and enable only the plugins you want
    $this->enableAllPluginsExcept(array('sf##OTHER_ORM##Plugin', 'sfCompat10Plugin'));
  }
}
