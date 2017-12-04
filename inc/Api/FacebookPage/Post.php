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
    private $ACCESS_TOKEN;

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


    public function __construct($post, $fb, $accessToken) {
        $this->post = $post;
        $this->FB = $fb;
        $this->ACCESS_TOKEN = $accessToken;
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

        $this->title = wp_strip_all_tags(substr($this->message, 0, 25) . '...');

        $this->parseMessage();
    }

    /**
    * Prepare data for post of type 'photo'
    */
    public function preparePhoto() {
        if ($this->type != 'photo') {
            return;
        }

        foreach ($this->getAttachments() as $attachment) {
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

        $event = new Event($this->getEvent());
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

        $content =  '<p>' . $this->message . '</p>' 
                    . '[embed]' . $this->link . '[/embed]';

        $content .= $this->originalLink();

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

        $content .= '<p>' . $this->message . '</p>';

        if ($this->statusType == 'shared_story') {
            $content .= '[embed]' . $this->link . '[/embed]';
        }

        $content .= $this->originalLink();

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

        $content .= '<p>' . $this->message . '</p>';
        
        if ($this->statusType != 'created_event') {
            $content .= '<p><a href="' . $this->link . '">' . $this->name . '</a></p>';
        }

        $content .= $this->originalLink();

        return $content;
    }

    private function originalLink() {
        return '<br><p><a href="' . $this->permalinkUrl . '">' . __('Link to original post', 'fbpp-textd') . '</a></p>';
    }

    /**
    * Get attachments of the post
    * @return   GraphEdge   Response of /attachments request
    */
    private function getAttachments() {
        $subattachmentLimit = 150; // option (1K max?)
        $attachmentsFields = 'url,title,type,media,subattachments.limit(' . $subattachmentLimit . '){title,media,type}';
        $attachmentsRequest = '/attachments' . '?fields=' . $attachmentsFields;

        // Set first image of album as cover if no single image in post
        $isAlbumCover = true; // option (default true)
        $hasCover = false;

        return $this->graphGet('/' . $this->id . $attachmentsRequest, $this->FB, $this->ACCESS_TOKEN)->getGraphEdge();
    }

    /**
    * Get event from the post
    * @return   GraphNode   Node of event
    */
    private function getEvent() {
        $eventRequest = '/' . $this->objectId . '?fields=cover';
        return $this->graphGet($eventRequest, $this->FB, $this->ACCESS_TOKEN)->getGraphNode();
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
