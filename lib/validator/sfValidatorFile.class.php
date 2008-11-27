<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// only in PHP 5.2
if (!defined('UPLOAD_ERR_EXTENSION'))
{
  define('UPLOAD_ERR_EXTENSION', 8);
}

/**
 * sfValidatorFile validates an uploaded file.
 *
 * @package    symfony
 * @subpackage validator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfValidatorFile extends sfValidatorBase
{
  /**
   * Configures the current validator.
   *
   * Available options:
   *
   *  * max_size:           The maximum file size
   *  * mime_types:         Allowed mime types array or category (available categories: web_images)
   *  * mime_type_guessers: An array of mime type guesser PHP callables (must return the mime type or null)
   *  * mime_categories:    An array of mime type categories (web_images is defined by default)
   *
   * There are 3 built-in mime type guessers:
   *
   *  * guessFromFileinfo:        Uses the finfo_open() function (from the Fileinfo PECL extension)
   *  * guessFromMimeContentType: Uses the mime_content_type() function (deprecated)
   *  * guessFromFileBinary:      Uses the file binary (only works on *nix system)
   *
   * Available error codes:
   *
   *  * max_size
   *  * mime_types
   *  * partial
   *  * no_tmp_dir
   *  * cant_write
   *  * extension
   *
   * @param array $options   An array of options
   * @param array $messages  An array of error messages
   *
   * @see sfValidatorBase
   */
  protected function configure($options = array(), $messages = array())
  {
    $this->addOption('max_size');
    $this->addOption('mime_types');
    $this->addOption('mime_type_guessers', array(
      array($this, 'guessFromFileinfo'),
      array($this, 'guessFromMimeContentType'),
      array($this, 'guessFromFileBinary'),
    ));
    $this->addOption('mime_categories', array(
      'web_images' => array(
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/gif',
    )));
    $this->addOption('validated_file_class', 'sfValidatedFile');

    $this->addMessage('max_size', 'File is too large (maximum is %max_size% bytes).');
    $this->addMessage('mime_types', 'Invalid mime type (%mime_type%).');
    $this->addMessage('partial', 'The uploaded file was only partially uploaded.');
    $this->addMessage('no_tmp_dir', 'Missing a temporary folder.');
    $this->addMessage('cant_write', 'Failed to write file to disk.');
    $this->addMessage('extension', 'File upload stopped by extension.');
  }

  /**
   * This validator always returns a sfValidatedFile object.
   *
   * The input value must be an array with the following keys:
   *
   *  * tmp_name: The absolute temporary path to the file
   *  * name:     The original file name (optional)
   *  * type:     The file content type (optional)
   *  * error:    The error code (optional)
   *  * size:     The file size in bytes (optional)
   *
   * @see sfValidatorBase
   */
  protected function doClean($value)
  {
    if (!is_array($value) || !isset($value['tmp_name']))
    {
      throw new sfValidatorError($this, 'invalid', array('value' => (string) $value));
    }

    if (!isset($value['name']))
    {
      $value['name'] = '';
    }

    if (!isset($value['error']))
    {
      $value['error'] = UPLOAD_ERR_OK;
    }

    if (!isset($value['size']))
    {
      $value['size'] = filesize($value['tmp_name']);
    }

    if (!isset($value['type']))
    {
      $value['type'] = 'application/octet-stream';
    }

    switch ($value['error'])
    {
      case UPLOAD_ERR_INI_SIZE:
        throw new sfValidatorError($this, 'max_size', array('max_size' => ini_get('upload_max_filesize'), 'size' => (int) $value['size']));
      case UPLOAD_ERR_FORM_SIZE:
        throw new sfValidatorError($this, 'max_size', array('max_size' => 0, 'size' => (int) $value['size']));
      case UPLOAD_ERR_PARTIAL:
        throw new sfValidatorError($this, 'partial');
      case UPLOAD_ERR_NO_TMP_DIR:
        throw new sfValidatorError($this, 'no_tmp_dir');
      case UPLOAD_ERR_CANT_WRITE:
        throw new sfValidatorError($this, 'no_cant_write');
      case UPLOAD_ERR_EXTENSION:
        throw new sfValidatorError($this, 'extension');
    }

    // check file size
    if ($this->hasOption('max_size') && $this->getOption('max_size') < (int) $value['size'])
    {
      throw new sfValidatorError($this, 'max_size', array('max_size' => $this->getOption('max_size'), 'size' => (int) $value['size']));
    }

    $mimeType = $this->getMimeType((string) $value['tmp_name'], (string) $value['type']);

    // check mime type
    if ($this->hasOption('mime_types'))
    {
      $mimeTypes = is_array($this->getOption('mime_types')) ? $this->getOption('mime_types') : $this->getMimeTypesFromCategory($this->getOption('mime_types'));
      if (!in_array($mimeType, $mimeTypes))
      {
        throw new sfValidatorError($this, 'mime_types', array('mime_types' => $mimeTypes, 'mime_type' => $mimeType));
      }
    }

    $class = $this->getOption('validated_file_class');

    return new $class($value['name'], $mimeType, $value['tmp_name'], $value['size']);
  }

  /**
   * Returns the mime type of a file.
   *
   * This methods call each mime_type_guessers option callables to
   * guess the mime type.
   *
   * @param  string $file      The absolute path of a file
   * @param  string $fallback  The default mime type to return if not guessable
   *
   * @return string The mime type of the file (fallback is returned if not guessable)
   */
  protected function getMimeType($file, $fallback)
  {
    foreach ($this->getOption('mime_type_guessers') as $method)
    {
      $type = call_user_func($method, $file);

      if (!is_null($type) && $type !== false)
      {
        return $type;
      }
    }

    return $fallback;
  }

  /**
   * Guess the file mime type with PECL Fileinfo extension
   *
   * @param  string $file  The absolute path of a file
   *
   * @return string The mime type of the file (null if not guessable)
   */
  protected function guessFromFileinfo($file)
  {
    if (!function_exists('finfo_open'))
    {
      return null;
    }

    if (!$finfo = new finfo(FILEINFO_MIME))
    {
      return null;
    }

    $type = $finfo->file($file);

    return $type;
  }

  /**
   * Guess the file mime type with mime_content_type function (deprecated)
   *
   * @param  string $file  The absolute path of a file
   *
   * @return string The mime type of the file (null if not guessable)
   */
  protected function guessFromMimeContentType($file)
  {
    if (!function_exists('mime_content_type'))
    {
      return null;
    }

    return mime_content_type($file);
  }

  /**
   * Guess the file mime type with the file binary (only available on *nix)
   *
   * @param  string $file  The absolute path of a file
   *
   * @return string The mime type of the file (null if not guessable)
   */
  protected function guessFromFileBinary($file)
  {
    ob_start();
    passthru(sprintf('file -bi %s 2>/dev/null', escapeshellarg($file)), $return);
    if ($return > 0)
    {
      return null;
    }
    $type = trim(ob_get_clean());

    if (!preg_match('#^([a-z0-9\-]+/[a-z0-9\-]+)#i', $type, $match))
    {
      // it's not a type, but an error message
      return null;
    }

    return $match[1];
  }

  protected function getMimeTypesFromCategory($category)
  {
    $categories = $this->getOption('mime_categories');

    if (!isset($categories[$category]))
    {
      throw new InvalidArgumentException(sprintf('Invalid mime type category "%s".', $category));
    }

    return $categories[$category];
  }

  /**
   * @see sfValidatorBase
   */
  protected function isEmpty($value)
  {
    // empty if the value is not an array
    // or if the value comes from PHP with an error of UPLOAD_ERR_NO_FILE
    return
      (!is_array($value))
        ||
      (is_array($value) && isset($value['error']) && UPLOAD_ERR_NO_FILE === $value['error']);
  }
}

/**
 * sfValidatedFile represents a validated uploaded file.
 *
 * @package    symfony
 * @subpackage validator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfValidatedFile
{
  protected
    $originalName = '',
    $tempName     = '',
    $savedName    = null,
    $type         = '',
    $size         = 0;

  /**
   * Constructor.
   *
   * @param string $originalName  The original file name
   * @param string $type          The file content type
   * @param string $tempName      The absolute temporary path to the file
   * @param int    $size          The file size (in bytes)
   */
  public function __construct($originalName, $type, $tempName, $size)
  {
    $this->originalName = $originalName;
    $this->tempName = $tempName;
    $this->type = $type;
    $this->size = $size;
  }

  /**
   * Returns the name of the saved file.
   */
  public function __toString()
  {
    return is_null($this->savedName) ? '' : $this->savedName;
  }

  /**
   * Saves the uploaded file.
   *
   * This method can throw exceptions if there is a problem when saving the file.
   *
   * @param  string $file      The absolute file path to save the file
   * @param  int    $fileMode  The octal mode to use for the new file
   * @param  bool   $create    Indicates that we should make the directory before moving the file
   * @param  int    $dirMode   The octal mode to use when creating the directory
   *
   * @return bool   true, if the file was saved, otherwise false
   *
   * @throws Exception
   */
  public function save($file, $fileMode = 0666, $create = true, $dirMode = 0777)
  {
    // get our directory path from the destination filename
    $directory = dirname($file);

    if (!is_readable($directory))
    {
      if ($create && !mkdir($directory, $dirMode, true))
      {
        // failed to create the directory
        throw new Exception(sprintf('Failed to create file upload directory "%s".', $directory));
      }

      // chmod the directory since it doesn't seem to work on recursive paths
      chmod($directory, $dirMode);
    }

    if (!is_dir($directory))
    {
      // the directory path exists but it's not a directory
      throw new Exception(sprintf('File upload path "%s" exists, but is not a directory.', $directory));
    }

    if (!is_writable($directory))
    {
      // the directory isn't writable
      throw new Exception(sprintf('File upload path "%s" is not writable.', $directory));
    }

    // copy the temp file to the destination file
    copy($this->getTempName(), $file);

    // chmod our file
    chmod($file, $fileMode);

    $this->savedName = $file;

    return true;
  }

  /**
   * Returns the file extension, based on the content type of the file.
   *
   * @param  string $default  The default extension to return if none was given
   *
   * @return string The extension (with the dot)
   */
  public function getExtension($default = '')
  {
    return $this->getExtensionFromType($this->type, $default);
  }

  /**
   * Returns the original uploaded file name extension.
   *
   * @param  string $default  The default extension to return if none was given
   *
   * @return string The extension of the uploaded name (with the dot)
   */
  public function getOriginalExtension($default = '')
  {
    return (false === $pos = strrpos($this->getOriginalName(), '.')) ? $default : substr($this->getOriginalName(), $pos);
  }

  /**
   * Returns true if the file has already been saved.
   *
   * @return Boolean true if the file has already been saved, false otherwise
   */
  public function isSaved()
  {
    return !is_null($this->savedName);
  }

  /**
   * Returns the path where the file has been saved
   *
   * @return string The path where the file has been saved
   */
  public function getSavedName()
  {
    return $this->savedName;
  }

  /**
   * Returns the original file name.
   *
   * @return string The file name
   */
  public function getOriginalName()
  {
    return $this->originalName;
  }

  /**
   * Returns the absolute temporary path to the uploaded file.
   *
   * @return string The temporary path
   */
  public function getTempName()
  {
    return $this->tempName;
  }

  /**
   * Returns the file content type.
   *
   * @return string The content type
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Returns the size of the uploaded file.
   *
   * @return int The file size
   */
  public function getSize()
  {
    return $this->size;
  }

  /**
   * Returns the extension associated with the given content type.
   *
   * @param  string $type     The content type
   * @param  string $default  The default extension to use
   *
   * @return string The extension (with the dot)
   */
  protected function getExtensionFromType($type, $default = '')
  {
    static $extensions = array(
      'application/andrew-inset' => 'ez',
      'application/appledouble' => 'base64',
      'application/applefile' => 'base64',
      'application/commonground' => 'dp',
      'application/cprplayer' => 'pqi',
      'application/dsptype' => 'tsp',
      'application/excel' => 'xls',
      'application/font-tdpfr' => 'pfr',
      'application/futuresplash' => 'spl',
      'application/hstu' => 'stk',
      'application/hyperstudio' => 'stk',
      'application/javascript' => 'js',
      'application/mac-binhex40' => 'hqx',
      'application/mac-compactpro' => 'cpt',
      'application/mbed' => 'mbd',
      'application/mirage' => 'mfp',
      'application/msword' => 'doc',
      'application/ocsp-request' => 'orq',
      'application/ocsp-response' => 'ors',
      'application/octet-stream' => 'bin',
      'application/octet-stream' => 'exe',
      'application/oda' => 'oda',
      'application/ogg' => 'ogg',
      'application/pdf' => 'pdf',
      'application/x-pdf' => 'pdf',
      'application/pgp-encrypted' => '7bit',
      'application/pgp-keys' => '7bit',
      'application/pgp-signature' => 'sig',
      'application/pkcs10' => 'p10',
      'application/pkcs7-mime' => 'p7m',
      'application/pkcs7-signature' => 'p7s',
      'application/pkix-cert' => 'cer',
      'application/pkix-crl' => 'crl',
      'application/pkix-pkipath' => 'pkipath',
      'application/pkixcmp' => 'pki',
      'application/postscript' => 'ai',
      'application/postscript' => 'eps',
      'application/postscript' => 'ps',
      'application/presentations' => 'shw',
      'application/prs.cww' => 'cw',
      'application/prs.nprend' => 'rnd',
      'application/quest' => 'qrt',
      'application/rtf' => 'rtf',
      'application/sgml-open-catalog' => 'soc',
      'application/sieve' => 'siv',
      'application/smil' => 'smi',
      'application/toolbook' => 'tbk',
      'application/vnd.3gpp.pic-bw-large' => 'plb',
      'application/vnd.3gpp.pic-bw-small' => 'psb',
      'application/vnd.3gpp.pic-bw-var' => 'pvb',
      'application/vnd.3gpp.sms' => 'sms',
      'application/vnd.acucorp' => 'atc',
      'application/vnd.adobe.xfdf' => 'xfdf',
      'application/vnd.amiga.amu' => 'ami',
      'application/vnd.blueice.multipass' => 'mpm',
      'application/vnd.cinderella' => 'cdy',
      'application/vnd.cosmocaller' => 'cmc',
      'application/vnd.criticaltools.wbs+xml' => 'wbs',
      'application/vnd.curl' => 'curl',
      'application/vnd.data-vision.rdz' => 'rdz',
      'application/vnd.dreamfactory' => 'dfac',
      'application/vnd.fsc.weblauch' => 'fsc',
      'application/vnd.genomatix.tuxedo' => 'txd',
      'application/vnd.hbci' => 'hbci',
      'application/vnd.hhe.lesson-player' => 'les',
      'application/vnd.hp-hpgl' => 'plt',
      'application/vnd.ibm.electronic-media' => 'emm',
      'application/vnd.ibm.rights-management' => 'irm',
      'application/vnd.ibm.secure-container' => 'sc',
      'application/vnd.ipunplugged.rcprofile' => 'rcprofile',
      'application/vnd.irepository.package+xml' => 'irp',
      'application/vnd.jisp' => 'jisp',
      'application/vnd.kde.karbon' => 'karbon',
      'application/vnd.kde.kchart' => 'chrt',
      'application/vnd.kde.kformula' => 'kfo',
      'application/vnd.kde.kivio' => 'flw',
      'application/vnd.kde.kontour' => 'kon',
      'application/vnd.kde.kpresenter' => 'kpr',
      'application/vnd.kde.kspread' => 'ksp',
      'application/vnd.kde.kword' => 'kwd',
      'application/vnd.kenameapp' => 'htke',
      'application/vnd.kidspiration' => 'kia',
      'application/vnd.kinar' => 'kne',
      'application/vnd.llamagraphics.life-balance.desktop' => 'lbd',
      'application/vnd.llamagraphics.life-balance.exchange+xml' => 'lbe',
      'application/vnd.lotus-1-2-3' => 'wks',
      'application/vnd.mcd' => 'mcd',
      'application/vnd.mfmp' => 'mfm',
      'application/vnd.micrografx.flo' => 'flo',
      'application/vnd.micrografx.igx' => 'igx',
      'application/vnd.mif' => 'mif',
      'application/vnd.mophun.application' => 'mpn',
      'application/vnd.mophun.certificate' => 'mpc',
      'application/vnd.mozilla.xul+xml' => 'xul',
      'application/vnd.ms-artgalry' => 'cil',
      'application/vnd.ms-asf' => 'asf',
      'application/vnd.ms-excel' => 'xls',
      'application/vnd.ms-lrm' => 'lrm',
      'application/vnd.ms-powerpoint' => 'ppt',
      'application/vnd.ms-project' => 'mpp',
      'application/vnd.ms-tnef' => 'base64',
      'application/vnd.ms-works' => 'base64',
      'application/vnd.ms-wpl' => 'wpl',
      'application/vnd.mseq' => 'mseq',
      'application/vnd.nervana' => 'ent',
      'application/vnd.nokia.radio-preset' => 'rpst',
      'application/vnd.nokia.radio-presets' => 'rpss',
      'application/vnd.oasis.opendocument.text' => 'odt',
      'application/vnd.oasis.opendocument.text-template' => 'ott',
      'application/vnd.oasis.opendocument.text-web' => 'oth',
      'application/vnd.oasis.opendocument.text-master' => 'odm',
      'application/vnd.oasis.opendocument.graphics' => 'odg',
      'application/vnd.oasis.opendocument.graphics-template' => 'otg',
      'application/vnd.oasis.opendocument.presentation' => 'odp',
      'application/vnd.oasis.opendocument.presentation-template' => 'otp',
      'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
      'application/vnd.oasis.opendocument.spreadsheet-template' => 'ots',
      'application/vnd.oasis.opendocument.chart' => 'odc',
      'application/vnd.oasis.opendocument.formula' => 'odf',
      'application/vnd.oasis.opendocument.database' => 'odb',
      'application/vnd.oasis.opendocument.image' => 'odi',
      'application/vnd.palm' => 'prc',
      'application/vnd.picsel' => 'efif',
      'application/vnd.pvi.ptid1' => 'pti',
      'application/vnd.quark.quarkxpress' => 'qxd',
      'application/vnd.sealed.doc' => 'sdoc',
      'application/vnd.sealed.eml' => 'seml',
      'application/vnd.sealed.mht' => 'smht',
      'application/vnd.sealed.ppt' => 'sppt',
      'application/vnd.sealed.xls' => 'sxls',
      'application/vnd.sealedmedia.softseal.html' => 'stml',
      'application/vnd.sealedmedia.softseal.pdf' => 'spdf',
      'application/vnd.seemail' => 'see',
      'application/vnd.smaf' => 'mmf',
      'application/vnd.sun.xml.calc' => 'sxc',
      'application/vnd.sun.xml.calc.template' => 'stc',
      'application/vnd.sun.xml.draw' => 'sxd',
      'application/vnd.sun.xml.draw.template' => 'std',
      'application/vnd.sun.xml.impress' => 'sxi',
      'application/vnd.sun.xml.impress.template' => 'sti',
      'application/vnd.sun.xml.math' => 'sxm',
      'application/vnd.sun.xml.writer' => 'sxw',
      'application/vnd.sun.xml.writer.global' => 'sxg',
      'application/vnd.sun.xml.writer.template' => 'stw',
      'application/vnd.sus-calendar' => 'sus',
      'application/vnd.vidsoft.vidconference' => 'vsc',
      'application/vnd.visio' => 'vsd',
      'application/vnd.visionary' => 'vis',
      'application/vnd.wap.sic' => 'sic',
      'application/vnd.wap.slc' => 'slc',
      'application/vnd.wap.wbxml' => 'wbxml',
      'application/vnd.wap.wmlc' => 'wmlc',
      'application/vnd.wap.wmlscriptc' => 'wmlsc',
      'application/vnd.webturbo' => 'wtb',
      'application/vnd.wordperfect' => 'wpd',
      'application/vnd.wqd' => 'wqd',
      'application/vnd.wv.csp+wbxml' => 'wv',
      'application/vnd.wv.csp+xml' => '8bit',
      'application/vnd.wv.ssp+xml' => '8bit',
      'application/vnd.yamaha.hv-dic' => 'hvd',
      'application/vnd.yamaha.hv-script' => 'hvs',
      'application/vnd.yamaha.hv-voice' => 'hvp',
      'application/vnd.yamaha.smaf-audio' => 'saf',
      'application/vnd.yamaha.smaf-phrase' => 'spf',
      'application/vocaltec-media-desc' => 'vmd',
      'application/vocaltec-media-file' => 'vmf',
      'application/vocaltec-talker' => 'vtk',
      'application/watcherinfo+xml' => 'wif',
      'application/wordperfect5.1' => 'wp5',
      'application/x-123' => 'wk',
      'application/x-7th_level_event' => '7ls',
      'application/x-authorware-bin' => 'aab',
      'application/x-authorware-map' => 'aam',
      'application/x-authorware-seg' => 'aas',
      'application/x-bcpio' => 'bcpio',
      'application/x-bleeper' => 'bleep',
      'application/x-bzip2' => 'bz2',
      'application/x-cdlink' => 'vcd',
      'application/x-chat' => 'chat',
      'application/x-chess-pgn' => 'pgn',
      'application/x-compress' => 'z',
      'application/x-cpio' => 'cpio',
      'application/x-cprplayer' => 'pqf',
      'application/x-csh' => 'csh',
      'application/x-cu-seeme' => 'csm',
      'application/x-cult3d-object' => 'co',
      'application/x-debian-package' => 'deb',
      'application/x-director' => 'dcr',
      'application/x-director' => 'dir',
      'application/x-director' => 'dxr',
      'application/x-dvi' => 'dvi',
      'application/x-envoy' => 'evy',
      'application/x-futuresplash' => 'spl',
      'application/x-gtar' => 'gtar',
      'application/x-gzip' => 'gz',
      'application/x-hdf' => 'hdf',
      'application/x-hep' => 'hep',
      'application/x-html+ruby' => 'rhtml',
      'application/x-httpd-miva' => 'mv',
      'application/x-httpd-php' => 'phtml',
      'application/x-ica' => 'ica',
      'application/x-imagemap' => 'imagemap',
      'application/x-ipix' => 'ipx',
      'application/x-ipscript' => 'ips',
      'application/x-java-archive' => 'jar',
      'application/x-java-jnlp-file' => 'jnlp',
      'application/x-java-serialized-object' => 'ser',
      'application/x-java-vm' => 'class',
      'application/x-javascript' => 'js',
      'application/x-koan' => 'skp',
      'application/x-latex' => 'latex',
      'application/x-mac-compactpro' => 'cpt',
      'application/x-maker' => 'frm',
      'application/x-mathcad' => 'mcd',
      'application/x-midi' => 'mid',
      'application/x-mif' => 'mif',
      'application/x-msaccess' => 'mda',
      'application/x-msdos-program' => 'cmd',
      'application/x-msdos-program' => 'com',
      'application/x-msdownload' => 'base64',
      'application/x-msexcel' => 'xls',
      'application/x-msword' => 'doc',
      'application/x-netcdf' => 'nc',
      'application/x-ns-proxy-autoconfig' => 'pac',
      'application/x-pagemaker' => 'pm5',
      'application/x-perl' => 'pl',
      'application/x-pn-realmedia' => 'rp',
      'application/x-python' => 'py',
      'application/x-quicktimeplayer' => 'qtl',
      'application/x-rar-compressed' => 'rar',
      'application/x-ruby' => 'rb',
      'application/x-sh' => 'sh',
      'application/x-shar' => 'shar',
      'application/x-shockwave-flash' => 'swf',
      'application/x-sprite' => 'spr',
      'application/x-spss' => 'sav',
      'application/x-spt' => 'spt',
      'application/x-stuffit' => 'sit',
      'application/x-sv4cpio' => 'sv4cpio',
      'application/x-sv4crc' => 'sv4crc',
      'application/x-tar' => 'tar',
      'application/x-tcl' => 'tcl',
      'application/x-tex' => 'tex',
      'application/x-texinfo' => 'texinfo',
      'application/x-troff' => 't',
      'application/x-troff-man' => 'man',
      'application/x-troff-me' => 'me',
      'application/x-troff-ms' => 'ms',
      'application/x-twinvq' => 'vqf',
      'application/x-twinvq-plugin' => 'vqe',
      'application/x-ustar' => 'ustar',
      'application/x-vmsbackup' => 'bck',
      'application/x-wais-source' => 'src',
      'application/x-wingz' => 'wz',
      'application/x-word' => 'base64',
      'application/x-wordperfect6.1' => 'wp6',
      'application/x-x509-ca-cert' => 'crt',
      'application/x-zip-compressed' => 'zip',
      'application/xhtml+xml' => 'xhtml',
      'application/zip' => 'zip',
      'audio/3gpp' => '3gpp',
      'audio/amr' => 'amr',
      'audio/amr-wb' => 'awb',
      'audio/basic' => 'au',
      'audio/evrc' => 'evc',
      'audio/l16' => 'l16',
      'audio/midi' => 'mid',
      'audio/mpeg' => 'mp3',
      'audio/mpeg' => 'mpga',
      'audio/prs.sid' => 'sid',
      'audio/qcelp' => 'qcp',
      'audio/smv' => 'smv',
      'audio/vnd.audiokoz' => 'koz',
      'audio/vnd.digital-winds' => 'eol',
      'audio/vnd.everad.plj' => 'plj',
      'audio/vnd.lucent.voice' => 'lvp',
      'audio/vnd.nokia.mobile-xmf' => 'mxmf',
      'audio/vnd.nortel.vbk' => 'vbk',
      'audio/vnd.nuera.ecelp4800' => 'ecelp4800',
      'audio/vnd.nuera.ecelp7470' => 'ecelp7470',
      'audio/vnd.nuera.ecelp9600' => 'ecelp9600',
      'audio/vnd.sealedmedia.softseal.mpeg' => 'smp3',
      'audio/voxware' => 'vox',
      'audio/x-aiff' => 'aif',
      'audio/x-mid' => 'mid',
      'audio/x-midi' => 'mid',
      'audio/x-mpeg' => 'mp2',
      'audio/x-mpegurl' => 'mpu',
      'audio/x-pn-realaudio' => 'ra',
      'audio/x-pn-realaudio' => 'rm',
      'audio/x-pn-realaudio-plugin' => 'rpm',
      'audio/x-realaudio' => 'ra',
      'audio/x-wav' => 'wav',
      'chemical/x-csml' => 'csm',
      'chemical/x-embl-dl-nucleotide' => 'emb',
      'chemical/x-gaussian-cube' => 'cube',
      'chemical/x-gaussian-input' => 'gau',
      'chemical/x-jcamp-dx' => 'jdx',
      'chemical/x-mdl-molfile' => 'mol',
      'chemical/x-mdl-rxnfile' => 'rxn',
      'chemical/x-mdl-tgf' => 'tgf',
      'chemical/x-mopac-input' => 'mop',
      'chemical/x-pdb' => 'pdb',
      'chemical/x-rasmol' => 'scr',
      'chemical/x-xyz' => 'xyz',
      'drawing/dwf' => 'dwf',
      'drawing/x-dwf' => 'dwf',
      'i-world/i-vrml' => 'ivr',
      'image/bmp' => 'bmp',
      'image/cewavelet' => 'wif',
      'image/cis-cod' => 'cod',
      'image/fif' => 'fif',
      'image/gif' => 'gif',
      'image/ief' => 'ief',
      'image/jp2' => 'jp2',
      'image/jpeg' => 'jpeg',
      'image/jpeg' => 'jpg',
      'image/jpm' => 'jpm',
      'image/jpx' => 'jpf',
      'image/pict' => 'pic',
      'image/pjpeg' => 'jpg',
      'image/png' => 'png',
      'image/targa' => 'tga',
      'image/tiff' => 'tif',
      'image/tiff' => 'tiff',
      'image/vn-svf' => 'svf',
      'image/vnd.dgn' => 'dgn',
      'image/vnd.djvu' => 'djvu',
      'image/vnd.dwg' => 'dwg',
      'image/vnd.glocalgraphics.pgb' => 'pgb',
      'image/vnd.microsoft.icon' => 'ico',
      'image/vnd.ms-modi' => 'mdi',
      'image/vnd.sealed.png' => 'spng',
      'image/vnd.sealedmedia.softseal.gif' => 'sgif',
      'image/vnd.sealedmedia.softseal.jpg' => 'sjpg',
      'image/vnd.wap.wbmp' => 'wbmp',
      'image/x-bmp' => 'bmp',
      'image/x-cmu-raster' => 'ras',
      'image/x-freehand' => 'fh4',
      'image/x-png' => 'png',
      'image/x-portable-anymap' => 'pnm',
      'image/x-portable-bitmap' => 'pbm',
      'image/x-portable-graymap' => 'pgm',
      'image/x-portable-pixmap' => 'ppm',
      'image/x-rgb' => 'rgb',
      'image/x-xbitmap' => 'xbm',
      'image/x-xpixmap' => 'xpm',
      'image/x-xwindowdump' => 'xwd',
      'message/external-body' => '8bit',
      'message/news' => '8bit',
      'message/partial' => '8bit',
      'message/rfc822' => '8bit',
      'model/iges' => 'igs',
      'model/mesh' => 'msh',
      'model/vnd.parasolid.transmit.binary' => 'x_b',
      'model/vnd.parasolid.transmit.text' => 'x_t',
      'model/vrml' => 'vrm',
      'model/vrml' => 'wrl',
      'multipart/alternative' => '8bit',
      'multipart/appledouble' => '8bit',
      'multipart/digest' => '8bit',
      'multipart/mixed' => '8bit',
      'multipart/parallel' => '8bit',
      'text/comma-separated-values' => 'csv',
      'text/css' => 'css',
      'text/html' => 'htm',
      'text/html' => 'html',
      'text/plain' => 'txt',
      'text/prs.fallenstein.rst' => 'rst',
      'text/richtext' => 'rtx',
      'text/rtf' => 'rtf',
      'text/sgml' => 'sgm',
      'text/sgml' => 'sgml',
      'text/tab-separated-values' => 'tsv',
      'text/vnd.net2phone.commcenter.command' => 'ccc',
      'text/vnd.sun.j2me.app-descriptor' => 'jad',
      'text/vnd.wap.si' => 'si',
      'text/vnd.wap.sl' => 'sl',
      'text/vnd.wap.wml' => 'wml',
      'text/vnd.wap.wmlscript' => 'wmls',
      'text/x-hdml' => 'hdml',
      'text/x-setext' => 'etx',
      'text/x-sgml' => 'sgml',
      'text/x-speech' => 'talk',
      'text/x-vcalendar' => 'vcs',
      'text/x-vcard' => 'vcf',
      'text/xml' => 'xml',
      'ulead/vrml' => 'uvr',
      'video/3gpp' => '3gp',
      'video/dl' => 'dl',
      'video/gl' => 'gl',
      'video/mj2' => 'mj2',
      'video/mpeg' => 'mp2',
      'video/mpeg' => 'mpeg',
      'video/mpeg' => 'mpg',
      'video/quicktime' => 'mov',
      'video/quicktime' => 'qt',
      'video/vdo' => 'vdo',
      'video/vivo' => 'viv',
      'video/vnd.fvt' => 'fvt',
      'video/vnd.mpegurl' => 'mxu',
      'video/vnd.nokia.interleaved-multimedia' => 'nim',
      'video/vnd.objectvideo' => 'mp4',
      'video/vnd.sealed.mpeg1' => 's11',
      'video/vnd.sealed.mpeg4' => 'smpg',
      'video/vnd.sealed.swf' => 'sswf',
      'video/vnd.sealedmedia.softseal.mov' => 'smov',
      'video/vnd.vivo' => 'viv',
      'video/vnd.vivo' => 'vivo',
      'video/x-fli' => 'fli',
      'video/x-ms-asf' => 'asf',
      'video/x-ms-wmv' => 'wmv',
      'video/x-msvideo' => 'avi',
      'video/x-sgi-movie' => 'movie',
      'x-chemical/x-pdb' => 'pdb',
      'x-chemical/x-xyz' => 'xyz',
      'x-conference/x-cooltalk' => 'ice',
      'x-drawing/dwf' => 'dwf',
      'x-world/x-d96' => 'd',
      'x-world/x-svr' => 'svr',
      'x-world/x-vream' => 'vrw',
      'x-world/x-vrml' => 'wrl',
    );

    return !$type ? $default : (isset($extensions[$type]) ? '.'.$extensions[$type] : $default);
  }
}
