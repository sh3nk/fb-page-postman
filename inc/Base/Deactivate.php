<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Base;

class Deactivate {
    
    public static function deactivate() {
        self::unscheduleEvents();
        flush_rewrite_rules();
    }

    private static function unscheduleEvents() {
        wp_clear_scheduled_hook('fbpp_refresh_event');
    }

}
