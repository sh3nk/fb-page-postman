<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Base;

class SettingsLink {

    public function register() {
        add_filter("plugin_action_links_" . FBPP__PLUGIN_NAME, array($this, 'settings_link'));
    }

    public function settings_link($links) {
        $settings_link = '<a href="admin.php?page=fbpp_plugin">' . __('Settings', 'fbpp-textd') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

}
