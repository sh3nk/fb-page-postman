<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman;

final class Init {

    /**
    * Store all the classes inside an array
    * @return array Full list of classes
    */
    public static function get_services() {
        return array(
            Pages\Settings::getClass(),
            Base\SettingsLink::getClass(),
            Api\FacebookPage\Main::getClass()
        );
    }

    /**
    * Loop through the classes, initialize them 
    * and call the register() method if it exists
    * @return
    */
    public static function register_services() {
        foreach(self::get_services() as $class) {
            $service = new $class();
            if (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

}
