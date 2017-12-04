<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Base;

class Activate {

    public static function activate() {
        self::scheduleEvents();

        // Creates new category if it does not already exist
        $catId = wp_create_category('facebook');
        if (is_wp_error($catId)) {
            echo $catId->get_error_message();
            exit;
        }

        flush_rewrite_rules();
    }

    private static function scheduleEvents() {
        if (!wp_next_scheduled('fbpp_refresh_event')) {
            wp_schedule_event(time(), 'fbpp_30min', 'fbpp_refresh_event');
        }
    }

}
