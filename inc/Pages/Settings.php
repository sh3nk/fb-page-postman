<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Pages;

use \FBPPostman\Api\SettingsApi;

class Settings {
    
    public $settings;

    public $pages = array();

    public function register() {
        $this->settings = new SettingsApi();
        
        $this->setPages();
        $this->setSettings();
        $this->setSections();
        $this->setFields();

        $this->settings->addPages($this->pages)->register();
    }

    public function setPages() {
        $this->pages = [
            [
                'page_title' => 'Facebook Page Postman', 
                'menu_title' => 'FB Postman', 
                'capability' => 'manage_options', 
                'menu_slug' => 'fbpp_plugin', 
                'callback' => array($this, 'cbDashboard'),
                'icon_url' => 'dashicons-facebook', 
                'position' => 110
            ]
        ];
    }

    public function setSettings() {
        $args = array(
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_page_id',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_app_id',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_app_secret',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_access_token',
                'callback' => array($this, 'cbOptionsGroup')
            )
        );

        $this->settings->setSettings($args);
    }

    public function setSections() {
        $args = array(
            array(
                'id' => 'fbpp_id_fields',
                'title' => __('Settings', 'fbpp-textd'),
                'callback' => array($this, 'cbAdminSection'),
                'page' => 'fbpp_plugin'
            )
        );

        $this->settings->setSections($args);
    }

    public function setFields() {
        $args = array(
            array(
                'id' => 'fbpp_page_id',
                'title' => 'Page ID',
                'callback' => array($this, 'cbPageId'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_page_id'
                )
            ),
            array(
                'id' => 'fbpp_app_id',
                'title' => 'App ID',
                'callback' => array($this, 'cbAppId'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_app_id'
                )
            ),
            array(
                'id' => 'fbpp_app_secret',
                'title' => 'App Secret',
                'callback' => array($this, 'cbAppSecret'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_app_secret'
                )
            ),
            array(
                'id' => 'fbpp_access_token',
                'title' => 'Access Token',
                'callback' => array($this, 'cbAccessToken'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_access_token',
                    // 'class' => 'example-class'
                )
            )

        );

        $this->settings->setFields($args);
    }

    public function cbDashboard() {
        return require_once(FBPP__PLUGIN_PATH . '/templates/settings.php');
    }

    public function cbOptionsGroup($input) {
        return $input;
    }

    public function cbAdminSection() {
        echo __('Create your app on developers.facebook.com to get necessary settings.', 'fbpp-textd');
    }

    public function cbPageId() {
        $value = esc_attr(get_option('fbpp_page_id'));
        echo    '<input type="text" class="regular-text" name="fbpp_page_id" value="' . $value . 
                '" placeholder="' . __('Unique ID of your Facebook group...', 'fbpp-textd') . '">';
    }

    public function cbAppId() {
        $value = esc_attr(get_option('fbpp_app_id'));
        echo    '<input type="text" class="regular-text" name="fbpp_app_id" value="' . $value . 
                '" placeholder="' . __('Your Facebook app ID...', 'fbpp-textd') . '">';
    }

    public function cbAppSecret() {
        $value = esc_attr(get_option('fbpp_app_secret'));
        echo    '<input type="text" class="regular-text" name="fbpp_app_secret" value="' . $value . 
                '" placeholder="' . __('Your Facebook app Secret code...', 'fbpp-textd') . '">';
    }

    public function cbAccessToken() {
        $value = esc_attr(get_option('fbpp_access_token'));
        echo    '<input type="text" class="regular-text" name="fbpp_access_token" value="' . $value . 
                '" placeholder="' . __('Your Facebook app access token...', 'fbpp-textd') . '">';
    }

}
