<?php
/**
* @package FacebookPagePostman
*/

namespace FBPPostman\Api\FacebookPage;

class Event {

    private $event;

    public $imgSrc = NULL;


    public function __construct($event) {
        $this->event = $event;
    }

    public function register() {
        if ($this->fieldsExist(array('cover', 'source'), $this->event)) {
            $this->imgSrc = $this->event->getField('cover')->getField('source');
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
