<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Api\FacebookPage;

use \FBPPostman\Api\FacebookPage\Main;

class Attachment extends Main {

    private $FB;
    private $attachment;

    public $type;
    public $imgSrc = NULL;
    public $subattachments = array();


    public function __construct($attachment, $fb) {
        $this->attachment = $attachment;
        $this->FB = $fb;
    }

    public function register() {
        if (FBPP__PHP_VERSION) {
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

        $subattachments = (FBPP__PHP_VERSION)   ? $this->attachment->getField('subattachments') 
                                                : $this->attachment['subattachments']['data'];
        
        foreach ($subattachments as $subattachment) {
            if (!$this->fieldsExist(array('media', 'image', 'src'), $subattachment)) {
                continue;
            }

            $imageId = (FBPP__PHP_VERSION)  ? $subattachment->getField('target')->getField('id') 
                                            : $subattachment['target']['id'];

            if (!isset($this->imgSrc)) {
                $this->imgSrc = $this->getImageUrl($imageId);
            }

            if ($isSharedStory) {
                break;
            }

            array_push($this->subattachments, $this->getImageUrl($imageId));
        }
    }

    /**
    * Set featured image of attachment
    */
    public function setSingleImage() {
        if ($this->fieldsExist(array('media', 'image', 'src'), $this->attachment)) {
            $imageId = (FBPP__PHP_VERSION)
                        ? $this->attachment->getField('target')->getField('id')
                        : $this->attachment['target']['id'];
            $this->imgSrc = $this->getImageUrl($imageId);
        }
    }

    /**
    * Set featured image of attachment from media field
    */
    public function setSingleImageMedia() {
        if ($this->fieldsExist(array('media', 'image', 'src'), $this->attachment)) {
            $this->imgSrc = (FBPP__PHP_VERSION)
                            ? $this->attachment->getField('media')->getField('image')->getField('src')
                            : $this->attachment['media']['image']['src'];
        }
    }

    /**
    * Get attachment image
    * @param    $id     Graph API Image Id
    * @return   string  Url of max resolution image
    * @return   null    On error
    */
    private function getImageUrl($id) {
        $photoRequest = '/' . $id . '?fields=images{source}';

        if (FBPP__PHP_VERSION) {
            $response = $this->graphGet($photoRequest, $this->FB);
            if ($response) {
                return $response->getGraphNode()->getField('images')[1]->getField('source');
            }
        } else {
            $response = $this->basicGet($photoRequest, $this->FB, false);
            if ($response) {
                return $response['images'][1]['source'];
            }
        }

        return;
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
