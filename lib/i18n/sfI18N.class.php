<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfI18N wraps the core i18n classes for a symfony context.
 *
 * @package    symfony
 * @subpackage i18n
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfI18N
{
  protected
    $dispatcher    = null,
    $cache         = null,
    $options       = array(),
    $culture       = 'en',
    $messageSource = null,
    $messageFormat = null;

  /**
   * Class constructor.
   *
   * @see initialize()
   */
  public function __construct(sfEventDispatcher $dispatcher, sfCache $cache = null, $options = array())
  {
    $this->initialize($dispatcher, $cache, $options);
  }

  /**
   * Initializes this class.
   *
   * @param sfEventDispatcher A sfEventDispatcher implementation instance
   * @param sfCache           A sfCache instance
   * @param array             An array of options
   */
  public function initialize(sfEventDispatcher $dispatcher, sfCache $cache = null, $options = array())
  {
    $this->dispatcher = $dispatcher;
    $this->cache      = $cache;

    if (isset($options['culture']))
    {
      $this->culture = $options['culture'];
      unset($options['culture']);
    }

    $this->options = array_merge(array(
      'source'              => 'XLIFF',
      'debug'               => false,
      'database'            => 'default',
      'untranslated_prefix' => '[T]',
      'untranslated_suffix' => '[/T]',
    ), $options);

    $dispatcher->connect('user.change_culture', array($this, 'listenToChangeCultureEvent'));
    $dispatcher->connect('controller.change_action', array($this, 'listenToChangeActionEvent'));
  }

  /**
   * Sets the message source.
   *
   * @param mixed  An array of i18n directories if message source is a sfMessageSource_File subclass, null otherwise
   * @param string The culture
   */
  public function setMessageSource($dirs, $culture = null)
  {
    if (is_null($dirs))
    {
      $this->messageSource = $this->createMessageSource();
    }
    else
    {
      $this->messageSource = sfMessageSource::factory('Aggregate', array_map(array($this, 'createMessageSource'), $dirs));
    }

    if (!is_null($this->cache))
    {
      $this->messageSource->setCache($this->cache);
    }

    if (!is_null($culture))
    {
      $this->setCulture($culture);
    }
    else
    {
      $this->messageSource->setCulture($this->culture);
    }

    $this->messageFormat = null;
  }

  /**
   * Returns a new message source.
   *
   * @param  mixed           An array of i18n directories to create a XLIFF or gettext message source, null otherwise
   *
   * @return sfMessageSource A sfMessageSource object
   */
  public function createMessageSource($dir = null)
  {
    return sfMessageSource::factory($this->options['source'], self::isMessageSourceFileBased($this->options['source']) ? $dir : $this->options['database']);
  }

  /**
   * Gets the current culture for i18n format objects.
   *
   * @return string The culture
   */
  public function getCulture()
  {
    return $this->culture;
  }

  /**
   * Sets the current culture for i18n format objects.
   *
   * @param string The culture
   */
  public function setCulture($culture)
  {
    $this->culture = $culture;

    if ($this->messageSource)
    {
      $this->messageSource->setCulture($culture);
      $this->messageFormat = null;
    }
  }

  /**
   * Gets the message source.
   *
   * @return sfMessageSource A sfMessageSource object
   */
  public function getMessageSource()
  {
    if (!isset($this->messageSource))
    {
      $this->setMessageSource(sfLoader::getI18NGlobalDirs(), $this->culture);
    }

    return $this->messageSource;
  }

  /**
   * Gets the message format.
   *
   * @return sfMessageFormat A sfMessageFormat object
   */
  public function getMessageFormat()
  {
    if (!isset($this->messageFormat))
    {
      $this->messageFormat = new sfMessageFormat($this->getMessageSource(), sfConfig::get('sf_charset'));

      if ($this->options['debug'])
      {
        $this->messageFormat->setUntranslatedPS(array($this->options['untranslated_prefix'], $this->options['untranslated_suffix']));
      }
    }

    return $this->messageFormat;
  }

  /**
   * Gets the translation for the given string
   *
   * @param  string The string to translate
   * @param  array  An array of arguments for the translation
   * @param  string The catalogue name
   *
   * @return string The translated string
   */
  public function __($string, $args = array(), $catalogue = 'messages')
  {
    return $this->getMessageFormat()->format($string, $args, $catalogue);
  }

  /**
   * Gets a country name.
   *
   * @param  string The ISO code
   * @param  string The culture for the translation
   *
   * @return string The country name
   */
  public function getCountry($iso, $culture = null)
  {
    $c = new sfCultureInfo(is_null($culture) ? $this->culture : $culture);
    $countries = $c->getCountries();

    return (array_key_exists($iso, $countries)) ? $countries[$iso] : '';
  }

  /**
   * Gets a native culture name.
   *
   * @param  string The culture
   *
   * @return string The culture name
   */
  public function getNativeName($culture)
  {
    $cult = new sfCultureInfo($culture);

    return $cult->getNativeName();
  }

  /**
   * Returns a timestamp from a date formatted with a given culture.
   *
   * @param  string  The formatted date as string
   * @param  string  The culture
   *
   * @return integer The timestamp
   */
  public function getTimestampForCulture($date, $culture = null)
  {
    list($d, $m, $y) = $this->getDateForCulture($date, is_null($culture) ? $this->culture : $culture);

    return is_null($d) ? null : mktime(0, 0, 0, $m, $d, $y);
  }

  /**
   * Returns the day, month and year from a date formatted with a given culture.
   *
   * @param  string  The formatted date as string
   * @param  string  The culture
   *
   * @return array   An array with the day, month and year
   */
  public function getDateForCulture($date, $culture = null)
  {
    if (!$date)
    {
      return null;
    }

    $dateFormatInfo = @sfDateTimeFormatInfo::getInstance(is_null($culture) ? $this->culture : $culture);
    $dateFormat = $dateFormatInfo->getShortDatePattern();

    // We construct the regexp based on date format
    $dateRegexp = preg_replace('/[dmy]+/i', '(\d+)', $dateFormat);

    // We parse date format to see where things are (m, d, y)
    $a = array(
      'd' => strpos($dateFormat, 'd'),
      'm' => strpos($dateFormat, 'M'),
      'y' => strpos($dateFormat, 'y'),
    );
    $tmp = array_flip($a);
    ksort($tmp);
    $i = 0;
    $c = array();
    foreach ($tmp as $value) $c[++$i] = $value;
    $datePositions = array_flip($c);

    // We find all elements
    if (preg_match("~$dateRegexp~", $date, $matches))
    {
      // We get matching timestamp
      return array($matches[$datePositions['d']], $matches[$datePositions['m']], $matches[$datePositions['y']]);
    }
    else
    {
      return null;
    }
  }

  /**
   * Returns true if messages are stored in a file.
   *
   * @param  string  The source name
   *
   * @return Boolean true if messages are stored in a file, false otherwise
   */
  static public function isMessageSourceFileBased($source)
  {
    $class = 'sfMessageSource_'.$source;

    return class_exists($class) && is_subclass_of($class, 'sfMessageSource_File');
  }

  /**
   * Listens to the user.change_culture event.
   *
   * @param sfEvent An sfEvent instance
   *
   */
  public function listenToChangeCultureEvent(sfEvent $event)
  {
    // change the message format object with the new culture
    $this->setCulture($event['culture']);
  }

  /**
   * Listens to the controller.change_action event.
   *
   * @param sfEvent An sfEvent instance
   *
   */
  public function listenToChangeActionEvent(sfEvent $event)
  {
    // change message source directory to our module
    $this->setMessageSource(sfLoader::getI18NDirs($event['module']));
  }
}
