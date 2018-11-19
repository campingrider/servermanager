<?php
/**
 * Central class managing all features and content
 *
 * Cares for managing all servers and services and displays them to the user.
 *
 * @package campingrider\servermanager
 */

namespace campingrider\servermanager;

/**
 * This class contains overall settings and controls the behavior of the system.
 *
 * @package campingrider\servermanager
 */
class Manager
{

    /**
     * Contains description for all settings which can be written out to an ini file.
     *
     * @var string[] $settings_descriptions
     */
    private static $settings_descriptions = array(
        'title'       => 'The following phrase is shown as a title for the server manager',
        'banner_path' => 'The following path points to the banner image',
    );

    /**
     * Contains default values for all settings.
     *
     * @var string[] $settings
     */
    private $settings = array(
        'title'       => 'Title not configured yet',
        'banner_path' => 'no_path.png',
    );

    /**
     * Constructor.
     *
     * @param string|string[] $settings Either string as Path of an ini-File containing all settings which shall
     *                                  be loaded or Array of strings containing all settings which shall be loaded.
     * @throws RuntimeException Thrown if the ini-File is not found at the given location.
     * @throws InvalidArgumentException Thrown if $settings is not of type string or array.
     */
    public function __construct($settings)
    {

        $loaded_settings = array();

        // load either from file or from array.
        if (is_string($settings)) {
            if (is_file($settings)) {
                $loaded_settings = parse_ini_file($settings);
            } else {
                throw new RuntimeException("File $settings not found!");
            }
        } elseif (is_array($settings)) {
            $loaded_settings = $settings;
        } else {
            throw new InvalidArgumentException(
                '$settings needs to be of type string or string[], not ' . gettype($settings) . '!'
            );
        }

        // overwrite default settings with loaded settings.
        $write_ini = false;
        foreach ($this->settings as $setting_id => $defaultvalue) {
            if (isset($loaded_settings[ $setting_id ])) {
                $this->settings[ $setting_id ] = $loaded_settings[ $setting_id ];
            } else {
                // setting was not found in loaded values - if loaded from ini file set to rewrite file!
                $write_ini = write_ini || (is_string($settings) && is_dir(dirname($settings)));
            }
        }

        // write ini with newer version if ini was not complete.
        if ($write_ini) {
            $content = "; General settings. Adjust to your needs.\n; ------------------------------ \n\n";
            foreach ($this->settings as $setting_id => $value) {
                if (isset(manager::$settings_descriptions[ $setting_id ])) {
                    $content .= '; ' . manager::$settings_descriptions[ $setting_id ] . "\n";
                }
                $content .= $setting_id . ' = "' . $value . '"' . "\n\n";
            }
            file_put_contents($settings, $content);
        }
    }

    /**
     * Getter for the title of the manager.
     *
     * @return string title of the manager
     */
    public function getTitle()
    {
        return $this->settings['title'];
    }
}
