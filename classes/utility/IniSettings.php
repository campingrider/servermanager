<?php
/**
 * contains class IniSettings
 *
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 * @author Camping_RIDER a.k.a. Janosch Zoller <janosch.zoller@gmx.de>
 *
 * @package campingrider\servermanager\utility
 */

namespace campingrider\servermanager\utility;

use campingrider\servermanager\exceptions\NotFoundException as NotFoundException;

/**
 * This class manages settings for all entities using ini files to store settings.
 *
 * @package campingrider\servermanager\utility
 */
class IniSettings {

  /**
   * Contains description for all settings which can be written out to an ini file.
   *
   * @var string[] $settings_descriptions
   */
  private $descriptions = array();

  /**
   * Path to the ini file to read/write settings
   *
   * @var string $ini_path
   */
  private $ini_path = "";

  /**
   * Internal storage for all settings.
   *
   * @var string[] $settings
   */
  private $settings = array();

  /**
   * Timestamp when the settings have been read the last time
   *
   * @var string $timestamp_last_read
   */
  private $timestamp_last_read = 0;

  /**
   * Constructor.
   *
   * @param string $ini_path contains the path to the the ini file to read/write settings
   * @param string[] $default_values contains default values for *all* settings
   * @param string[] $descriptions contains descriptions for *all* settings
   * @throws NotFoundException Thrown if the ini-File is not found at the given location or if the
   */
  public function __construct($ini_path, $default_values, $descriptions = array())
  {
    $this->settings_descriptions = $descriptions;
    $this->settings = $default_values;
    $this->ini_path = $ini_path;

    if (!is_file($ini_path)) {
      throw new NotFoundException("File $ini_path not found!");
    }
  }

  /**
   * Get specific setting
   * 
   * @param string $key contains key of the setting
   * @throws NotFoundException if the setting could not be found in settings
   *
   * @return mixed
   */
  public function get($key)
  {
    $this->read_ini();
    if (\array_key_exists($key, $this->settings)) {
      return $this->settings[$key];
    } else {
      throw new NotFoundException("Setting $key could not be found in Settings!");
    }
  }

  /**
   * Load settings from ini file if necessary
   *
   * @throws NotFoundException Thrown if the ini file is not found where it was expected.
   *
   * @return void
   */
  private function read_ini()
  {
    if (!is_file($this->ini_path)) {
      throw new NotFoundException("File $this->ini_path not found!");
    }

    if ($this->timestamp_last_read < \filemtime($this->ini_path)) {

      $this->timestamp_last_read = \filemtime($this->ini_path);
      $loaded_settings = \parse_ini_file($this->ini_path);
      
      // overwrite default settings with loaded settings.
      $write_ini = false;
      
      foreach ($this->settings as $setting_id => $defaultvalue) {
        if (isset($loaded_settings[ $setting_id ])) {
          $this->settings[ $setting_id ] = $loaded_settings[ $setting_id ];
        } else {
          // setting was not found in loaded values - set to rewrite file!
          $write_ini = true;
        }
      }
      
      // write ini with newer version if ini was not complete.
      if ($write_ini) {
        $this->write_ini();
      }
    }
  }

  /**
   * Set specific setting and trigger rewriting the file
   * 
   * @param string $key contains key of the setting
   * @param mixed $value contains value of the setting - must be convertible to string
   * @throws NotFoundException if the setting could not be found in settings
   *
   * @return boolean Whether the operation was successful
   */
  public function set($key, $value)
  {
    $old_value = $this->get($key);

    if ($value != $old_value) {
      // try to set new value and write ini file
      try {
        $this->settings[$key] = $value;
        return $this->write_ini();
      } catch (Exception $e) {
        // something went wrong - revert setting and return false
        $this->settings[$key] = $old_value;
        return false;
      }
    } else {
      return true;
    }
  }

  /**
   * Write internally stored settings to file
   *
   * @throws NotFoundException Thrown if the ini file is not found where it was expected.
   *
   * @return boolean Whether the operation was successful
   */
  private function write_ini()
  {
    if (!is_file($this->ini_path)) {
      throw new NotFoundException("File $this->ini_path not found!");
    }

    $content = "; General settings. Adjust to your needs. Caution: Syntax errors and other breaking changes might result in the whole file being overwritten by default values! You may want to backup this file before changing.\n; ------------------------------ \n\n";
    foreach ($this->settings as $setting_id => $value) {
        if (isset($this->settings_descriptions[ $setting_id ])) {
            $content .= '; ' . $this->settings_descriptions[ $setting_id ] . "\n";
        }
        $content .= $setting_id . ' = "' . $value . '"' . "\n\n";
    }

    // only write ini if it hasn't been altered in between!
    if ($this->timestamp_last_read == \filemtime($this->ini_path)) {
      $written_bytes = file_put_contents($this->ini_path, $content);
      if ($written_bytes !== false) {
        $this->timestamp_last_read = \filemtime($this->ini_path);
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    } 
  }

}
