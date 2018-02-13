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
        $this->pages = array(
            array(
                'page_title' => 'Facebook Page Postman', 
                'menu_title' => 'FB Postman', 
                'capability' => 'manage_options', 
                'menu_slug' => 'fbpp_plugin', 
                'callback' => array($this, 'cbDashboard'),
                'icon_url' => 'dashicons-facebook', 
                'position' => 110
            )
        );
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
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_limit',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_text_posts',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_image_posts',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_album_posts',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_albums',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_events',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_videos',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_links',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_profile',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_include_cover',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_content_category',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_content_tags',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_content_original',
                'callback' => array($this, 'cbOptionsGroup')
            ),
            array(
                'option_group' => 'fbpp_options_group',
                'option_name' => 'fbpp_content_original_text',
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
            ),
            array(
                'id' => 'fbpp_include_fields',
                'title' => __('Post include settings', 'fbpp-textd'),
                'callback' => array($this, 'cbIncludeFields'),
                'page' => 'fbpp_plugin'
            ),
            array(
                'id' => 'fbpp_content_fields',
                'title' => __('Post content settings', 'fbpp-textd'),
                'callback' => array($this, 'cbContentFields'),
                'page' => 'fbpp_plugin'
            )
        );

        $this->settings->setSections($args);
    }

    public function setFields() {
        $args = array(
            array(
                'id' => 'fbpp_page_id',
                'title' => 'Page ID *',
                'callback' => array($this, 'cbPageId'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_page_id'
                )
            ),
            array(
                'id' => 'fbpp_app_id',
                'title' => 'App ID *',
                'callback' => array($this, 'cbAppId'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_app_id'
                )
            ),
            array(
                'id' => 'fbpp_app_secret',
                'title' => 'App Secret *',
                'callback' => array($this, 'cbAppSecret'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_app_secret'
                )
            ),
            array(
                'id' => 'fbpp_access_token',
                'title' => 'Access Token *',
                'callback' => array($this, 'cbAccessToken'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_id_fields',
                'args' => array(
                    'label_for' => 'fbpp_access_token',
                    // 'class' => 'example-class'
                )
            ),
            array(
                'id' => 'fbpp_include_limit',
                'title' => 'Limit of posts to fetch on a single execution (max 100)',
                'callback' => array($this, 'cbIncludeLimit'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_limit'
                )
            ),
            array(
                'id' => 'fbpp_include_text_posts',
                'title' => 'Include text-only posts',
                'callback' => array($this, 'cbIncludeTextPosts'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_text_posts'
                )
            ),
            array(
                'id' => 'fbpp_include_image_posts',
                'title' => 'Include single image posts',
                'callback' => array($this, 'cbIncludeImagePosts'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_image_posts'
                )
            ),
            array(
                'id' => 'fbpp_include_album_posts',
                'title' => 'Include posts with albums',
                'callback' => array($this, 'cbIncludeAlbumPosts'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_album_posts'
                )
            ),
            array(
                'id' => 'fbpp_include_albums',
                'title' => 'Include full albums',
                'callback' => array($this, 'cbIncludeAlbums'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_albums'
                )
            ),
            array(
                'id' => 'fbpp_include_events',
                'title' => 'Include event posts',
                'callback' => array($this, 'cbIncludeEvents'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_events'
                )
            ),
            array(
                'id' => 'fbpp_include_videos',
                'title' => 'Include video posts',
                'callback' => array($this, 'cbIncludeVideos'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_videos'
                )
            ),
            array(
                'id' => 'fbpp_include_links',
                'title' => 'Include link posts',
                'callback' => array($this, 'cbIncludeLinks'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_links'
                )
            ),
            array(
                'id' => 'fbpp_include_profile',
                'title' => 'Include profile picture updates',
                'callback' => array($this, 'cbIncludeProfile'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_profile'
                )
            ),
            array(
                'id' => 'fbpp_include_cover',
                'title' => 'Include cover photo updates',
                'callback' => array($this, 'cbIncludeCover'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_include_fields',
                'args' => array(
                    'label_for' => 'fbpp_include_cover'
                )
            ),
            array(
                'id' => 'fbpp_content_category',
                'title' => 'Posts category name',
                'callback' => array($this, 'cbContentCategory'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_content_fields',
                'args' => array(
                    'label_for' => 'fbpp_content_category'
                )
            ),
            array(
                'id' => 'fbpp_content_tags',
                'title' => 'Convert tags to hyperlinks',
                'callback' => array($this, 'cbContentTags'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_content_fields',
                'args' => array(
                    'label_for' => 'fbpp_content_tags'
                )
            ),
            array(
                'id' => 'fbpp_content_original',
                'title' => 'Show link to original post on FB',
                'callback' => array($this, 'cbContentOriginal'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_content_fields',
                'args' => array(
                    'label_for' => 'fbpp_content_original'
                )
            ),
            array(
                'id' => 'fbpp_content_original_text',
                'title' => 'Text for link to original post',
                'callback' => array($this, 'cbContentOriginalText'),
                'page' => 'fbpp_plugin',
                'section' => 'fbpp_content_fields',
                'args' => array(
                    'label_for' => 'fbpp_content_original_text'
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
        echo __('Create your app on developers.facebook.com to get the necessary settings.', 'fbpp-textd');
    }

    public function cbIncludeFields() {
        echo __('Select which Facebook types of posts to include.', 'fbpp-textd');
    }

    public function cbContentFields() {
        echo __('Options for content output.', 'fbpp-textd');
    }

    public function cbPageId() {
        $value = esc_attr(get_option('fbpp_page_id'));
        echo    '<input type="text" class="regular-text" id="fbpp_page_id" name="fbpp_page_id" value="' . $value . 
                '" placeholder="' . __('Unique ID of your Facebook group', 'fbpp-textd') . '" required>';
    }

    public function cbAppId() {
        $value = esc_attr(get_option('fbpp_app_id'));
        echo    '<input type="text" class="regular-text" id="fbpp_app_id" name="fbpp_app_id" value="' . $value . 
                '" placeholder="' . __('Your Facebook App ID', 'fbpp-textd') . '" required>';
    }

    public function cbAppSecret() {
        $value = esc_attr(get_option('fbpp_app_secret'));
        echo    '<input type="text" class="regular-text" id="fbpp_app_secret" name="fbpp_app_secret" value="' . $value . 
                '" placeholder="' . __('Your Facebook app Secret code', 'fbpp-textd') . '" required>';
    }

    public function cbAccessToken() {
        $value = esc_attr(get_option('fbpp_access_token'));
        echo    '<input type="text" class="regular-text" id="fbpp_access_token" name="fbpp_access_token" value="' . $value . 
                '" placeholder="' . __('Your Facebook app Access token', 'fbpp-textd') . '" required>';
    }

    public function cbIncludeLimit() {
        $value = esc_attr(get_option('fbpp_include_limit'));
        if (!$value) {
            $value = 10;
        }

        echo    '<input type="number" id="fbpp_include_limit" name="fbpp_include_limit" ' . 
                'value="' . $value . '" min="1" max="100">';
    }

    public function cbIncludeTextPosts() {
        $value = esc_attr(get_option('fbpp_include_text_posts'));
        echo    '<input type="checkbox" id="fbpp_include_text_posts" name="fbpp_include_text_posts" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeImagePosts() {
        $value = esc_attr(get_option('fbpp_include_image_posts'));
        echo    '<input type="checkbox" id="fbpp_include_image_posts" name="fbpp_include_image_posts" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeAlbumPosts() {
        $value = esc_attr(get_option('fbpp_include_album_posts'));
        echo    '<input type="checkbox" id="fbpp_include_album_posts" name="fbpp_include_album_posts" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeAlbums() {
        $value = esc_attr(get_option('fbpp_include_albums'));
        echo    '<input type="checkbox" id="fbpp_include_albums" name="fbpp_include_albums" value="1"' 
                . checked(1, $value, false) . '>';
    }
    
    public function cbIncludeEvents() {
        $value = esc_attr(get_option('fbpp_include_events'));
        echo    '<input type="checkbox" id="fbpp_include_events" name="fbpp_include_events" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeVideos() {
        $value = esc_attr(get_option('fbpp_include_videos'));
        echo    '<input type="checkbox" id="fbpp_include_videos" name="fbpp_include_videos" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeLinks() {
        $value = esc_attr(get_option('fbpp_include_links'));
        echo    '<input type="checkbox" id="fbpp_include_links" name="fbpp_include_links" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeProfile() {
        $value = esc_attr(get_option('fbpp_include_profile'));
        echo    '<input type="checkbox" id="fbpp_include_profile" name="fbpp_include_profile" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbIncludeCover() {
        $value = esc_attr(get_option('fbpp_include_cover'));
        echo    '<input type="checkbox" id="fbpp_include_cover" name="fbpp_include_cover" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbContentCategory() {
        $value = esc_attr(get_option('fbpp_content_category'));
        if (!$value) {
            $value = 'facebook';
        }

        echo    '<input type="text" class="regular-text" id="fbpp_content_category" name="fbpp_content_category" value="' . $value . 
                '" placeholder="' . __('Category name', 'fbpp-textd') . '">';
    }

    public function cbContentTags() {
        $value = esc_attr(get_option('fbpp_content_tags'));
        echo    '<input type="checkbox" id="fbpp_content_tags" name="fbpp_content_tags" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbContentOriginal() {
        $value = esc_attr(get_option('fbpp_content_original'));
        echo    '<input type="checkbox" id="fbpp_content_original" name="fbpp_content_original" value="1"' 
                . checked(1, $value, false) . '>';
    }

    public function cbContentOriginalText() {
        $value = esc_attr(get_option('fbpp_content_original_text'));
        echo    '<input type="text" class="regular-text" id="fbpp_content_original_text" name="fbpp_content_original_text" value="' . $value . 
                '" placeholder="' . __('Link to original post', 'fbpp-textd') . '">';
    }

    public static function getClass() {
        return get_class();
    }

}
