<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Api\FacebookPage;

use \FBPPostman\Api\FacebookPage\Post;

class Main {

    private $FB;
    private $accessToken;
    private $pageId;
    private $publishQueue = array(); // array of posts to be published on next hook

    private $fields = 'id,created_time,message,message_tags,status_type,type,link,name,description,object_id,permalink_url';
    private $limit = 10;
    private $publishHook = 'fbpp_refresh_event';
    private $categoryName = 'facebook';
    private $executionTime = 1200; // 20min

    private $errorText = ''; // Text for error notices


    public function register() {
        // Get options set in plugin settings page
        $appId = get_option('fbpp_app_id');
        $appSecret = get_option('fbpp_app_secret');
        $this->accessToken = get_option('fbpp_access_token');
        $this->pageId = get_option('fbpp_page_id');

        $this->limit = get_option('fbpp_include_limit') ? get_option('fbpp_include_limit') : 10;
        
        // Check if all settings are set, else display notice and stop execution
        if (!$appId || !$appSecret || !$this->accessToken || !$this->pageId) {
            add_action('admin_notices', array($this, 'errorNoticeSettings'));
            return;
        }

        // Creates new category if it does not already exist
        $this->categoryName = get_option('fbpp_content_category') ? get_option('fbpp_content_category') : 'facebook';
        add_action('admin_init', array($this, 'getCategory'));

        if (FBPP__PHP_VERSION) {
            // Require FB PHP SDK
            require_once FBPP__PLUGIN_PATH . '/vendor/Facebook/autoload.php';

            // Create object for core Graph API interaction
            $this->FB = new \Facebook\Facebook(array(
                'app_id' => $appId,
                'app_secret' => $appSecret,
                'default_access_token' => $this->accessToken,
                'default_graph_version' => FBPP__GRAPH_VERSION
            ));
        }

        // Register handler for publishing posts
        add_action($this->publishHook, array($this, 'latestPosts'));
    }

    /**
    * Request latest $limit posts from FB and add them to an array for publishment
    * Performed on $publishHook
    */
    public function latestPosts() {
        // Set longer exectuion time (process runs in background)
        $oldExecutionTime = ini_set('max_execution_time', $this->executionTime);

        $posts = $this->getPosts();
        if (!$posts) {
            return; // No posts received; possibly FB connection error
        }

        foreach($posts as $post) {
            if (FBPP__PHP_VERSION) {
                $post = new Post($post, $this->FB);
            } else {
                $post = new Post($post, $this->accessToken);
            }
            $post->register();

            // Check if post was already published to WP
            global $wpdb;
            $isProcessed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
                'fb_id',
                $post->id
            ));

            // Skip already published posts
            if ($isProcessed) {
                continue;
            }

            array_push($this->publishQueue, $post);
        }

        $this->publishPosts();

        // ini_restore('max_execution_time');
        $oldExecutionTime = ini_set('max_execution_time', $oldExecutionTime);
    }

    /**
    * Parse content from post, set featured image
    */
    private function publishPosts() {
        $categoryId = get_cat_ID($this->categoryName);

        foreach ($this->publishQueue as $post) {
            $content = '';

            if ($post->type == 'video') {
                if (!get_option('fbpp_include_videos')) {
                    continue;
                }
                $content = $post->getContentVideo();
            } elseif ($post->type == 'photo') {
                $photoType = $post->preparePhoto();
                if (    $photoType == 'cover_photo' && !get_option('fbpp_include_cover')
                    ||  $photoType == 'photo' && !get_option('fbpp_include_image_posts')
                    ||  $photoType == 'album' && !get_option('fbpp_include_album_posts')
                    ||  $photoType == 'profile_media' && !get_option('fbpp_include_profile')
                ) {
                    continue;
                }
                $content = $post->getContentPhoto();
            } elseif ($post->type == 'event') {
                if (!get_option('fbpp_include_events')) {
                    continue;
                }
                $post->prepareEvent();
                $content = $post->getContentEvent();
            } elseif ($post->type == 'link') {
                if (!get_option('fbpp_include_links')) {
                    continue;
                }
                $post->prepareLink();
                $content = $post->getContentLink();
            } else {
                if (!get_option('fbpp_include_text_posts')) {
                    continue;
                }
                $content = $post->getContentDefault();
            }

            // Available params: https://developer.wordpress.org/reference/functions/wp_insert_post/#parameters
            $insertArgs = array(
                'post_author' => 1,
                'post_date' => $post->createdTime,
                'post_content' => make_clickable($content), // convert link strings to <a> elements (wp function)
                'post_title' => $post->title,
                'post_status' => 'publish',
                'post_category' => array($categoryId),
                'meta_input' => array(
                    'fb_id' => $post->id // Meta key to avoid duplicates
                )
            );

            // Check for duplicate title
            while (get_page_by_title($insertArgs['post_title'], OBJECT, 'post') != null) {
                $insertArgs['post_title'] .= ' (1)';
            }

            // Insert post
            $postId = wp_insert_post($insertArgs, true);

            // Check for errors
            if (is_wp_error($postId)) {
                add_action('admin_notices', array($this, 'errorNoticeCreate'));
                // echo $postId->get_error_message();
                return;
            }
            
            // Add Featured image
            if (isset($post->featuredImage)) {
                $attachId = $this->addPostImage($post->featuredImage, $postId);
                if ($attachId !== false) {
                    set_post_thumbnail($postId, $attachId);
                }
            }

            // Add gallery
            if ($post->albums) {
                // IDs of every image attached to post
                $attachedIds = array(); 

                foreach ($post->albums as $album) {
                    foreach ($album as $image) {
                        $attachId = $this->addPostImage($image, $postId);
                        if ($attachId !== false) {
                            array_push($attachedIds, $attachId);
                        }
                    }
                }

                // Append gallery with shortcode
                if (count($attachedIds) > 1) {
                    $content .= '[gallery link="file" ids="';
                    for ($i = 0; $i < count($attachedIds); $i++) {
                        if ($i > 0) {
                            $content .= ',';
                        }
                        $content .= $attachedIds[$i];
                    }
                    $content .= '"]';


                    $postId = wp_update_post(array(
                        'ID' => $postId,
                        'post_content' => $content
                    ), true);

                    if (is_wp_error($postId)) {
                        add_action('admin_notices', array($this, 'errorNoticeCreate'));
                        return;
                    }
                }
            }

            $this->log('Published ' . $post->title);
            
        }

        $this->publishQueue = array(); // reset array
    }

    /**
    * Save image from link at properly attach it to a post
    * @param    $imageUrl   URL of image to attach
    * @param    $postId     WP Post ID to attach image to
    * @return   int         ID of created attachment/image in WP database
    * @return   false       On error
    */
    private function addPostImage($imageUrl, $postId) {
        $uploadDir = wp_upload_dir();

        // Get image data from FB image source url
        $imageData = wp_remote_get($imageUrl);
        if (is_array($imageData) && !is_wp_error($imageData)) {
            $imageData = $imageData['body'];
        } else {
            return false;
        }

        // Image name without the FB query parameters
        $filename = strtok(basename($imageUrl), '?');

        // Retrieve file type from file name
        $filetype = wp_check_filetype($filename, null);
        if (empty($filetype['type'])) {
            return false;
        }

        $filepath = '';
        // Get save path... 
        // if specific path is available save there, 
        // else save to base uploads folder
        if (wp_mkdir_p($uploadDir['path'])) {
            $filepath = $uploadDir['path'] . '/' . $filename;
        } else {
            $filepath = $uploadDir['basedir'] . '/' . $filename;
        }

        // Save image
        if (file_put_contents($filepath, $imageData) === false ) {
            return false;
        }

        $attachment = array(
            'guid' => $uploadDir['url'] . '/' . $filename,
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Create attachment and attach it to $postId
        $attachId = wp_insert_attachment($attachment, $filepath, $postId);

        if (is_wp_error($attachId)) {
            return false;
        }

        // Required for wp_generate_attachment_metadata()
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    
        // Generate metadata
        $attachData = wp_generate_attachment_metadata($attachId, $filepath);

        // Update database record
        wp_update_attachment_metadata($attachId, $attachData);

        return $attachId;
    }

    /**
    * Get posts from page
    * @return   GraphEdge   Object of posts with requested fields
    * @return   array       Array of posts if using basic get
    * @return   null        On error
    */
    private function getPosts() {
        $request = '/' . $this->pageId . '/posts?fields=' . $this->fields . '&limit=' . $this->limit;
        if (FBPP__PHP_VERSION) {
            $response = $this->graphGet($request, $this->FB);
            if ($response) {
                return $response->getGraphEdge();
            }
        } else {
            $response = $this->basicGet($request, $this->accessToken, true);
            if ($response) {
                return $response;
            }
        }
        
        return;
    }

    /**
    * Graph API GET request with error checking
    * @param    $req    Request string
    * @param    $fb     Facebook PHP SDK core object
    * @return   Facebook\GraphNodes\*   PHP Graph API Response object
    * @return   null    On error
    */
    protected function graphGet($req, $fb) {
        try {
            return $fb->get(
                $req
            );
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $this->errorText = 'Graph returned an error: ' . $e->getMessage();
            add_action('admin_notices', array($this, 'errorNoticeFacebook'));
            return;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $this->errorText = 'Facebook SDK returned an error: ' . $e->getMessage();
            add_action('admin_notices', array($this, 'errorNoticeFacebook'));
            return;
        }
    }

    /**
    * Basic GET request to FB Graph endpoints
    * @param    $req            Request string
    * @param    $accessToken    Graph API access token
    * @param    $returnData     Set to true to return 'data' entry of response array
    * @return   array   Array of response data
    * @return   null    On error
    */
    protected function basicGet($req, $accessToken, $returnData) {
        $requestString =    'https://graph.facebook.com/' . FBPP__GRAPH_VERSION . '/' 
                            . $req . '&access_token=' . $accessToken;
        $data = file_get_contents($requestString);
        if ($data === false) {
            return;
        }
        $data = json_decode($data, true);
        if ($returnData) {
            return $data['data']; // Response also includes 'paging' for pagination cursors
        } else {
            return $data;
        }
    }

    /**
    * Handler to display error notice for FB API errors
    */
    public function errorNoticeFacebook() {
        echo    '<div class="error notice"><p>Facebook Page Postman: ' . 
                $this->errorText . '</p></div>';
    }

    /**
    * Handler to display error notice for settings on WP admin
    */
    public function errorNoticeSettings() {
        echo    '<div class="error notice is-dismissible"><p>Facebook Page Postman: ' . 
                __('Required settings not found. Check plugin settings menu.', 'fbpp-textd') . '</p></div>';
    }

    /**
    * Handler to display error notice for post creation on WP admin
    */
    public function errorNoticeCreate() {
        echo    '<div class="error notice is-dismissible"><p>Facebook Page Postman: ' . 
                __('Could not create post.', 'fbpp-textd') . '</p></div>';
    }

    /**
    * Handler function to create category if it does not exist
    * Added to 'admin_init' hook
    */
    public function getCategory() {
        $catId = wp_create_category($this->categoryName);
        if (is_wp_error($catId)) {
            echo $catId->get_error_message();
            exit;
        }
    }

    public static function getClass() {
         return get_class();
    }

    private function resetLog() {
        file_put_contents(FBPP__PLUGIN_PATH . '/log.txt', "");
    }

    protected function log($contents) {
        file_put_contents(FBPP__PLUGIN_PATH . '/log.txt', date("Y-m-d H:i:s") . "    " . $contents . "\n", FILE_APPEND);
    }

}
