<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Api\FacebookPage;

class Attachment {

    private $attachment;

    public $type;
    public $imgSrc = NULL;
    public $subattachments = array();


    public function __construct($attachment) {
        $this->attachment = $attachment;
    }

    public function register() {
        if (FBPP__PHP_VERSION == '5.6') {
            $this->type = $this->attachment->getField('type');
        } else {
            $this->type = $this->attachment['type'];
        }
    }

    /**
    * Parse instance of Attachment for subattachments
    * @param    $isSharedStory  Is $statusType of Attachment's Post 'shared_story'
    * @return   bool    Is cover image for Attachment's Post set ($imgSrc)
    */
    public function setSubattachments($isSharedStory) {
        if (!$this->fieldsExist('subattachments', $this->attachment)) {
            return;
        }

        $subattachments = (FBPP__PHP_VERSION == '5.6')  ? $this->attachment->getField('subattachments') 
                                                        : $this->attachment['subattachments']['data'];
        
        foreach ($subattachments as $subattachment) {
            if (!$this->fieldsExist(array('media', 'image', 'src'), $subattachment)) {
                continue;
            }

            if (!isset($this->imgSrc)) {
                $this->imgSrc = (FBPP__PHP_VERSION == '5.6')
                                ? $subattachment->getField('media')->getField('image')->getField('src')
                                : $subattachment['media']['image']['src'];
            }

            if ($isSharedStory) {
                break;
            }

            if (FBPP__PHP_VERSION == '5.6') {
                array_push($this->subattachments, $subattachment->getField('media')->getField('image')->getField('src'));
            } else {
                array_push($this->subattachments, $subattachment['media']['image']['src']);
            }
        }
    }

    /**
    * Set featured image of attachment
    */
    public function setSingleImage() {
        if ($this->fieldsExist(array('media', 'image', 'src'), $this->attachment)) {
            $this->imgSrc = (FBPP__PHP_VERSION == '5.6')
                            ? $this->attachment->getField('media')->getField('image')->getField('src')
                            : $this->attachment['media']['image']['src'];
        }
    }

    /**
    * Check if array keys exist
    * Nested keys should be ordered according to their hierarchy
    * @param    $keys   Name of key or array of consecutive keys to check for
    * @param    $array  Array to check for keys
    * @return   bool    Are $keys set in $array
    */
    private function fieldsExist($keys, $array) {
        if (is_array($keys)) {
            foreach($keys as $key) {
                if (!isset($array[$key])) {
                    return false;
                }
                $array = $array[$key];
            }
        } else {
            if (!isset($array[$keys])) {
                return false;
            }
        }
        return true;
    }
}
