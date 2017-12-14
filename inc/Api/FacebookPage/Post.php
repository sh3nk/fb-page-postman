<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Api\FacebookPage;

use \FBPPostman\Api\FacebookPage\Main;
use \FBPPostman\Api\FacebookPage\Attachment;
use \FBPPostman\Api\FacebookPage\Event;

class Post extends Main {

    // Connection to FB: FB object if using PHP SDK, otherwise access token
    private $FB;

    private $post;

    public $statusType;
    public $type;

    public $id;
    public $message;
    private $messageTags;
    public $createdTime;
    public $permalinkUrl; // Link to original post on Facebook
    public $link; // Link to media content
    public $name;
    public $description;
    public $objectId;
    public $title;
    public $featuredImage = NULL;
    public $imageTitle; // Title of image for link share posts
    public $albums = array();

    private $styleClassDefault = 'fbpp-default';
    private $styleClassVideo = 'fbpp-video';
    private $styleClassPhoto = 'fbpp-photo';
    private $styleClassEvent = 'fbpp-event';
    private $styleClassLink = 'fbpp-link';
    private $styleClassMessage = 'fbpp-message';
    private $styleClassOriginalLink = 'fbpp-original-link';
    private $styleClassVideoWrapper = 'fbpp-video-wrapper';
    private $styleClassVideoDescription = 'fbpp-video-description';
    private $styleClassEventLink = 'fbpp-event-link';
    private $styleClassLinkDescription = 'fbpp-link-description';

    private $timeZoneName = 'Europe/Ljubljana';

    public function __construct($post, $fb) {
        $this->post = $post;
        $this->FB = $fb;
    }

    public function register() {
        if (FBPP__PHP_VERSION) {
            $this->statusType = $this->post->getField('status_type');
            $this->type = $this->post->getField('type');
            $this->id = $this->post->getField('id');
            $this->message = $this->post->getField('message');
            $this->messageTags = $this->post->getField('message_tags');
            if ($this->messageTags && !$this->messageTags->getFieldNames()) { // Mark no tags as null
                $this->messageTags = null;
            }
            // $this->createdTime = $this->post->getField('created_time');
            $timeZone = new \DateTimeZone($this->timeZoneName);
            $this->createdTime = date_format($this->post->getField('created_time')->setTimezone($timeZone), 'Y-m-d H:i:s');
            $this->permalinkUrl = $this->post->getField('permalink_url');
            $this->link = $this->post->getField('link');            
            $this->name = $this->post->getField('name');
            $this->description = $this->post->getField('description');
            $this->objectId = $this->post->getField('object_id');
        } else {
            $this->statusType = (isset($this->post['status_type'])) ? $this->post['status_type'] : null;
            $this->type = (isset($this->post['type'])) ? $this->post['type'] : null;
            $this->id = (isset($this->post['id'])) ? $this->post['id'] : null;
            $this->message = (isset($this->post['message'])) ? $this->post['message'] : null;
            $this->messageTags = (isset($this->post['message_tags'])) ? $this->post['message_tags'] : null;
            $this->createdTime = (isset($this->post['created_time'])) 
                                    ? date('Y-m-d H:i:s', strtotime($this->post['created_time'])) 
                                    : date('Y-m-d H:i:s');
            $this->permalinkUrl = (isset($this->post['permalink_url'])) ? $this->post['permalink_url'] : null;
            $this->link = (isset($this->post['link'])) ? $this->post['link'] : null;
            $this->name = (isset($this->post['name'])) ? $this->post['name'] : null;
            $this->description = (isset($this->post['description'])) ? $this->post['description'] : null;
            $this->objectId = (isset($this->post['object_id'])) ? $this->post['object_id'] : null;
        }

        $this->title = wp_strip_all_tags($this->getTitle());

        if ($this->description) {
            $this->description = wp_strip_all_tags($this->description);
            $this->description = str_replace(["\r\n", "\r", "\n"], "<br>", $this->description);
        }

        $this->parseMessage();
    }

    /**
    * Prepare data for post of type 'photo'
    * @return   null    On error / no images
    * @return   string  Type of first attachment
    */
    public function preparePhoto() {
        if ($this->type != 'photo') {
            return;
        }

        $attachments = $this->getAttachments();
        if (!$attachments) {
            return;
        }

        $firstType = null;

        foreach ($attachments as $attachment) {
            $attachment = new Attachment($attachment, $this->FB);
            $attachment->register();

            if (!$firstType) {
                $firstType = $attachment->type;
            }

            if ($attachment->type == 'cover_photo') {
                $attachment->setSingleImageMedia();
                if (!get_option('fbpp_include_cover')) {
                    return 'cover_photo';
                }
            } elseif (strpos($attachment->type, 'photo') !== false) {
                $attachment->setSingleImage();
            } elseif ($attachment->type == 'new_album') {
                // new_album attachment target entry points to album url not image
                // only take subattachments / set featured image from subattachments
                $isSharedStory = ($this->statusType == 'shared_story');
                $attachment->setSubattachments($isSharedStory);
                if (!get_option('fbpp_include_albums')) {
                    if (!isset($this->featuredImage) && isset($attachment->imgSrc)) {
                        $this->featuredImage = $attachment->imgSrc;
                    }
                    return 'album';
                }
                array_push($this->albums, $attachment->subattachments);
            } elseif (strpos($attachment->type, 'album') !== false) {
                $attachment->setSingleImage();
                $isSharedStory = ($this->statusType == 'shared_story');
                $attachment->setSubattachments($isSharedStory);
                if (!get_option('fbpp_include_albums')) {
                    if (!isset($this->featuredImage) && isset($attachment->imgSrc)) {
                        $this->featuredImage = $attachment->imgSrc;
                    }
                    return 'album';
                }
                array_push($this->albums, $attachment->subattachments);
            } elseif ($attachment->type == 'profile_media') {
                $attachment->setSingleImageMedia();
                if (!get_option('fbpp_include_profile')) {
                    return 'profile_media';
                }
            }

            if (!isset($this->featuredImage) && isset($attachment->imgSrc)) {
                $this->featuredImage = $attachment->imgSrc;
            }
        }

        return $firstType;
    }

    /**
    * Prepare data for post of type 'event'
    */
    public function prepareEvent() {
        if ($this->type != 'event') {
            return;
        }

        $eventData = $this->getEvent();
        if (!$eventData) {
            return;
        }

        $event = new Event($eventData);
        $event->register();
        
        if (!isset($this->featuredImage) && isset($event->imgSrc)) {
            $this->featuredImage = $event->imgSrc;
        }
    }

    /**
    * Prepare data for post of type 'link'
    */
    public function prepareLink() {
        if ($this->type != 'link') {
            return;
        }

        $attachments = $this->getAttachments();
        if (!$attachments) {
            return;
        }

        foreach ($attachments as $attachment) {
            $attachment = new Attachment($attachment, $this->FB);
            $attachment->register();

            if ($attachment->type == 'share') {
                $attachment->setSingleImageMedia();
            }

            if (!isset($this->featuredImage) && isset($attachment->imgSrc)) {
                $this->featuredImage = $attachment->imgSrc;

                // Set image name from external url (not FB image)
                $components = parse_url($attachment->imgSrc);
                parse_str($components['query'], $query);
                $this->imageTitle = basename($query['url']);

                break;
            }
        }
    }

    /**
    * Compose content for post of type 'video'
    * @return   string  Composed content of post
    */
    public function getContentVideo() {
        if ($this->type != 'video') {
            return __('Wrong post type', 'fbpp-textd');
        }

        $content =  '<div class="' . $this->styleClassMessage . '"><p>' . $this->message . '</p></div>';
        $content .= '<div class="' . $this->styleClassVideoWrapper . '">[embed]' . $this->link . '[/embed]</div>';

        $pieces = parse_url($this->link);
        $domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            $domain = $regs['domain'];
        }

        if ($this->description && $domain && $domain == 'facebook.com') {
            $content .= '<div class="' . $this->styleClassVideoDescription . '">' . $this->description . '</div>';
        }

        $content .= $this->originalLink();
        $content = '<div class="' . $this->styleClassVideo . '">' . $content . '</div>';

        return $content;
    }

    /**
    * Compose content for post of type 'photo'
    * @return   string  Composed content of post
    */
    public function getContentPhoto() {
        if ($this->type != 'photo') {
            return __('Wrong post type', 'fbpp-textd');
        }

        $content = '';

        $content .= '<div class="' . $this->styleClassMessage . '"><p>' . $this->message . '</p></div>';

        if ($this->statusType == 'shared_story') {
            $content .= '[embed]' . $this->link . '[/embed]';
        }

        $content .= $this->originalLink();
        $content = '<div class="' . $this->styleClassPhoto . '">' . $content . '</div>';

        return $content;
    }

    /**
    * Compose content for post of type 'event'
    * @return   string  Composed content of post
    */
    public function getContentEvent() {
        if ($this->type != 'event') {
            return __('Wrong post type', 'fbpp-textd');
        }

        $content = '';

        $content .= '<div class="' . $this->styleClassMessage . '"><p>' . $this->message . '</p></div>';
        
        if ($this->statusType != 'created_event') {
            $content .= '<p class="' . $this->styleClassEventLink . '"><a href="' 
                        . $this->link . '">' . $this->name . '</a></p>';
        }

        $content .= $this->originalLink();
        $content = '<div class="' . $this->styleClassEvent . '">' . $content . '</div>';

        return $content;
    }

    /**
    * Compose content for default post - only message
    * @return   string  Composed content of post
    */
    public function getContentLink() {
        if ($this->type != 'link') {
            return __('Wrong post type', 'fbpp-textd');
        }

        $content = '<div class="' . $this->styleClassMessage . '"><p>' . $this->message . '</p></div>';

        $content .= '<p class="' . $this->styleClassLink . '"><a href=" ' . $this->link . '">' 
                    . $this->name . '</a></p>';

        if ($this->description) {
            $content .= '<div class="' . $this->styleClassLinkDescription . '">' . $this->description . '</div>';
        }

        $content .= $this->originalLink();

        return $content;
    }

    /**
    * Compose content for default post - only message
    * @return   string  Composed content of post
    */
    public function getContentDefault() {
        $content = '<div class="' . $this->styleClassDefault . '"><p>' . $this->message . '</p></div>';
        $content .= $this->originalLink();

        return $content;
    }

    private function originalLink() {
        return  '<br><p class="' . $this->styleClassOriginalLink . '"><a href="' 
                . $this->permalinkUrl . '">' . __('Link to original post', 'fbpp-textd') . '</a></p>';
    }

    /**
    * Get attachments of the post
    * @return   GraphEdge   Response of /attachments request
    * @return   array       Array of attachments if using basic get
    * @return   null        On error
    */
    private function getAttachments() {
        $subattachmentLimit = 150; // option (1K max?)
        $attachmentsFields = 'type,media,target,subattachments.limit(' . $subattachmentLimit . '){media,type,target}';
        $attachmentsRequest = '/' . $this->id . '/attachments' . '?fields=' . $attachmentsFields;

        // Set first image of album as cover if no single image in post
        $isAlbumCover = true; // option (default true)
        $hasCover = false;

        if (FBPP__PHP_VERSION) {
            $response = $this->graphGet($attachmentsRequest, $this->FB);
            if ($response) {
                return $response->getGraphEdge();
            }
        } else {
            $response = $this->basicGet($attachmentsRequest, $this->FB, true);
            if ($response) {
                return $response;
            }
        }

        return;
    }

    /**
    * Get event from the post
    * @return   GraphNode   Node of event
    * @return   array       Array of event properties if using basic get
    * @return   null        On error
    */
    private function getEvent() {
        $eventRequest = '/' . $this->objectId . '?fields=cover';

        if (FBPP__PHP_VERSION) {
            $response = $this->graphGet($eventRequest, $this->FB);
            if ($response) {
                return $response->getGraphNode();
            }
        } else {
            $response = $this->basicGet($eventRequest, $this->FB, false);
            if ($response) {
                return $response;
            }
        }

        return;
    }

    /**
    * Generate title for post by parsing message body
    * @return   string  Title for WP post
    */
    private function getTitle() {
        $title = '';
        $defaultTitle = 'Facebook';

        if ($this->createdTime) {
            $defaultTitle .= ' ' . $this->createdTime;
        }

        if (!$this->message) {
            return $defaultTitle;
        }

        $length = mb_strlen($this->message);

        if ($length <= 1) {
            return $defaultTitle;
        }
        if ($length <= 60) {
            // Remove punctuation at end of string
            $title = rtrim($this->message, ".,;: \t\n\r");
            return $title;
        }
    
        // Title should be 50-60 chars (displayed in Google results)
        $title = mb_substr($this->message, 0, 57);
    
        $newlineIdx = false;
        $lastSpaceIdx = false;
    
        if (   ($newlineIdx = mb_strrpos($title, "\r\n")) !== false 
            || ($newlineIdx = mb_strrpos($title, "\n")) !== false 
            || ($newlineIdx = mb_strrpos($title, "\r")) !== false) {
    
            // Take string including last full paragraph
            $title = mb_substr($title, 0, $newlineIdx);
            $title = rtrim($title, ".,;: \t\n\r");
            $title = preg_replace("/\s+.?$/", '', $title); // Remove trailing single letters
        } else if (($lastSpaceIdx = mb_strrpos($title, ' ')) !== false) {
            // Take string including last full word
            $title = mb_substr($title, 0, $lastSpaceIdx);
        }
    
        if ($newlineIdx === false) {
            $title = rtrim($title, ".!?,;: \t\n\r");
            $title = preg_replace("/\s+.?$/", '', $title);
            $title .= '...';
        }
        
        if (strlen($title) < 1) {
            return $defaultTitle;
        }

        return $title;
    }

    /**
    * Handle message tags and EOLs
    * Set to $this->message var
    */
    private function parseMessage() {
        $message = $this->message;
        $messageTags = $this->messageTags;

        // Properly strip all HTML tags
        $message = wp_strip_all_tags($message);
    
        // Handle message tags - links to page tags
        // Field 'message_tags' must exist and must be non-empty
        if ($messageTags) {
            $additionalOffset = 0; // increase offset if <a> tags are inserted
            
            foreach($messageTags as $tag) {
                $tagType = (FBPP__PHP_VERSION) ? $tag->getField('type') : $tag['type'];
                if ($tagType != 'page' && $tagType != 'event') {
                    continue;
                }
    
                if (FBPP__PHP_VERSION) {
                    $tagLink = '<a href="https://www.facebook.com/' . $tag->getField('id') . '">';
                    $tagOffset = $tag->getField('offset') + $additionalOffset;
                    $tagLength = $tag->getField('length');
                } else {
                    $tagLink = '<a href="https://www.facebook.com/' . $tag['id'] . '">';
                    $tagOffset = $tag['offset'] + $additionalOffset;
                    $tagLength = $tag['length'];
                }
    
                $message = mb_substr($message, 0, $tagOffset)
                            . $tagLink 
                            . mb_substr($message, $tagOffset, $tagLength) . '</a>'
                            . mb_substr($message, $tagOffset + $tagLength);
    
                $additionalOffset += strlen($tagLink) + 4; // + 4 = </a> tag
            }
        }
    
        // Insert newlines
        $message = str_replace(["\r\n", "\r", "\n"], "<br>", $message);

        $this->message = $message;
    }

}
