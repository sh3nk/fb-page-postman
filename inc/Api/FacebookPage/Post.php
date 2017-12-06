<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Api\FacebookPage;

use \FBPPostman\Api\FacebookPage\Main;
use \FBPPostman\Api\FacebookPage\Attachment;
use \FBPPostman\Api\FacebookPage\Event;

class Post extends Main {

    private $FB;

    private $post;

    public $statusType;
    public $type;

    public $id;
    public $message;
    public $createdTime;
    public $permalinkUrl; // Link to original post on Facebook
    public $link; // Link to media content
    public $name;
    public $objectId;
    public $title;
    public $featuredImage = NULL;
    public $albums = array();

    private $styleClassVideo = 'fbpp-video';
    private $styleClassPhoto = 'fbpp-photo';
    private $styleClassEvent = 'fbpp-event';
    private $styleClassMessage = 'fbpp-message';
    private $styleClassOriginalLink = 'fbpp-original-link';
    private $styleClassVideoWrapper = 'fbpp-video-wrapper';
    private $styleClassEventLink = 'fbpp-event-link';

    public function __construct($post, $fb) {
        $this->post = $post;
        $this->FB = $fb;
    }

    public function register() {
        $this->statusType = $this->post->getField('status_type');
        $this->type = $this->post->getField('type');
        $this->id = $this->post->getField('id');
        $this->message = $this->post->getField('message');
        // $this->createdTime = $this->post->getField('created_time');
        $this->createdTime = date_format($this->post->getField('created_time'), 'Y-m-d H:i:s');
        $this->permalinkUrl = $this->post->getField('permalink_url');
        $this->link = $this->post->getField('link');
        $this->name = $this->post->getField('name');
        $this->objectId = $this->post->getField('object_id');

        $this->title = wp_strip_all_tags($this->getTitle());

        $this->parseMessage();
    }

    /**
    * Prepare data for post of type 'photo'
    */
    public function preparePhoto() {
        if ($this->type != 'photo') {
            return;
        }

        $attachments = $this->getAttachments();
        if (!$attachments) {
            return;
        }

        foreach ($attachments as $attachment) {
            $attachment = new Attachment($attachment);
            $attachment->register();

            if (strpos($attachment->type, 'photo') !== false) {
                $attachment->setSingleImage();
            } elseif (strpos($attachment->type, 'album') !== false) {
                $attachment->setSingleImage();
                $isSharedStory = ($this->statusType == 'shared_story');
                $attachment->setSubattachments($isSharedStory);
                array_push($this->albums, $attachment->subattachments);
            }

            if (!isset($this->featuredImage) && isset($attachment->imgSrc)) {
                $this->featuredImage = $attachment->imgSrc;
            }
        }
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
    * Compose content for post of type 'video'
    * @return   string  Composed content of post
    */
    public function getContentVideo() {
        if ($this->type != 'video') {
            return __('Wrong post type', 'fbpp-textd');
        }

        $content =  '<div class="' . $this->styleClassMessage . '"><p>' . $this->message . '</p></div>';
        $content .= '<div class="' . $this->styleClassVideoWrapper . '">[embed]' . $this->link . '[/embed]</div>';

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

    private function originalLink() {
        return  '<br><p class="' . $this->styleClassOriginalLink . '"><a href="' 
                . $this->permalinkUrl . '">' . __('Link to original post', 'fbpp-textd') . '</a></p>';
    }

    /**
    * Get attachments of the post
    * @return   GraphEdge   Response of /attachments request
    * @return   null        On error
    */
    private function getAttachments() {
        $subattachmentLimit = 150; // option (1K max?)
        $attachmentsFields = 'url,title,type,media,subattachments.limit(' . $subattachmentLimit . '){title,media,type}';
        $attachmentsRequest = '/attachments' . '?fields=' . $attachmentsFields;

        // Set first image of album as cover if no single image in post
        $isAlbumCover = true; // option (default true)
        $hasCover = false;

        $response = $this->graphGet('/' . $this->id . $attachmentsRequest, $this->FB);
        if ($response) {
            return $response->getGraphEdge();
        }

        return;
    }

    /**
    * Get event from the post
    * @return   GraphNode   Node of event
    * @return   null        On error
    */
    private function getEvent() {
        $eventRequest = '/' . $this->objectId . '?fields=cover';

        $response = $this->graphGet($eventRequest, $this->FB);
        if ($response) {
            return $response->getGraphNode();
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
        $messageTags = $this->post->getField('message_tags');

        // Properly strip all HTML tags
        $message = wp_strip_all_tags($message);
    
        // Handle message tags - links to page tags
        // Field 'message_tags' must exist and must be non-empty
        if ($messageTags && $messageTags->getFieldNames()) {
            $additionalOffset = 0; // increase offset if <a> tags are inserted
            
            foreach($messageTags as $tag) {
                if ($tag->getField('type') != 'page' && $tag->getField('type') != 'event') {
                    continue;
                }
    
                $tagLink = '<a href="https://www.facebook.com/' . $tag->getField('id') . '">';
    
                $tagOffset = $tag->getField('offset') + $additionalOffset;
                $tagLength = $tag->getField('length');
    
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
