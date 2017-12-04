<?php
session_start();
require_once __DIR__ . '/src/Facebook/autoload.php';

$FB = new Facebook\Facebook([
    'app_id' => '706471066211654',
    'app_secret' => 'd197a263051bb13327b233e502e01043',
    'default_graph_version' => 'v2.10',
]);

$ACCESS_TOKEN = '706471066211654|3oG9BWlaYiAhhT-ZjSTnjk2YKhE';

$pageId = 'OrkesterSencur';

$fields = 'id,created_time,message,message_tags,status_type,type,link,name,object_id,permalink_url';

$limit = 25;

$feedRequest = '/feed?fields=' . $fields . '&limit=' . $limit;


$posts = graphGet('/' . $pageId . $feedRequest, $FB, $ACCESS_TOKEN)->getGraphEdge();
foreach ($posts as $post) {
    $message = $post->getField('message');    
    $messageTags = $post->getField('message_tags');

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

    // Get fields
    $statusType = $post->getField('status_type');
    $type = $post->getField('type');

    // printr($post);

    // Begin output
    echo '<h1>Post title</h1>';
    echo '<p>' . $message . '</p>';
    echo '<p>' . date_format($post->getField('created_time'), 'd. m. Y, H:i') . '</p>';
    echo '<p>Link to original post: <a href="' . $post->getField('permalink_url') . '">Facebook</a></p>';

    echo '<p>Status type: ' . $statusType . '</p>';
    echo '<p>Media type: ' . $type . '</p>';
    
    if ($type == 'video') {
        echo '<p><a href="' . $post->getField('link') . '">Video link</a></p>';
    } elseif ($type == 'photo') {
        echo '<p>Get attachments</p>';

        $subattachmentLimit = 150; // option (1K max?)
        $attachmentsFields = 'url,title,type,media,subattachments.limit(' . $subattachmentLimit . '){title,media,type}';
        $attachmentsRequest = '/attachments' . '?fields=' . $attachmentsFields;

        // Set first image of album as cover if no single image in post
        $isAlbumCover = true; // option (default true)
        $hasCover = false;

        $attachments = graphGet('/' . $post->getField('id') . $attachmentsRequest, $FB, $ACCESS_TOKEN)->getGraphEdge();
        foreach ($attachments as $attachment) {
            // printr($attachment);

            $attachmentType = $attachment->getField('type');

            if (strpos($attachmentType, 'photo') !== false) {
                if (fieldsExist(array('media', 'image', 'src'), $attachment)) {
                    echoCover($attachment);
                    $hasCover = true;
                }
            } elseif (strpos($attachmentType, 'album') !== false) {
                if (fieldsExist(array('media', 'image', 'src'), $attachment)) {
                    echoCover($attachment);
                    $hasCover = true;
                }

                if (!fieldsExist('subattachments', $attachment)) {
                    continue;
                }

                $subattachments = $attachment->getField('subattachments');

                echo '<ol>';
                foreach ($subattachments as $subattachment) {
                    if (!fieldsExist(array('media', 'image', 'src'), $subattachment)) {
                        continue;
                    }

                    if (!$hasCover) {
                        echoCover($subattachment);
                        $hasCover = true;
                    }

                    if ($statusType == 'shared_story') {
                        break;
                    }

                    echo '<li>';
                    echo $subattachment->getField('media')->getField('image')->getField('src');
                    echo '</li>';
                }
                echo '</ol>';
            }
        }
    } elseif ($type == 'event') {
        // On 'created_event' event details and event page are linked in the post itself
        if ($statusType != 'created_event') {
            echo '<p><a href="' . $post->getField('link') . '">Event link - ' . $post->getField('name') . '</a></p>';
        }

        
        // Get cover photo
        $eventRequest = '/' . $post->getField('object_id') . '?fields=cover';
        $event = graphGet($eventRequest, $FB, $ACCESS_TOKEN)->getGraphNode();
        if (fieldsExist(array('cover', 'source'), $event)) {
            echo '<img src="' . $event->getField('cover')->getField('source') . '" style="display: block; margin: 0 auto;"></img>';
        }
    }

    if ($statusType == 'shared_story') {
        // Videos from other sites (other than FB, eg. youtube) are considered 'shared_story', shouldnt be linked twice
        if ($type != 'video') {
            echo '<p><a href="' . $post->getField('link') . '">Shared story link - ' . $post->getField('name') . '</a></p>';
        }
    }
    
    echo '<hr>';
}

// Echo cover image
function echoCover($item) {
    echo '<img src="' . $item->getField('media')->getField('image')->getField('src') . '" style="display: block; margin: 0 auto;"></img>';
}


// Check if array keys exist
// Nested keys should be ordered according to their hierarchy: 
// ['key1', 'key2', 'key3'] for
// [key1] => (
//     [key2] => (
//         [key3] => '...'
//     )
// )
function fieldsExist($keys, $array) {
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

// Graph API get request with error checking
function graphGet($req, $fb, $accessToken) {
    try {
        return $fb->get(
            $req,
            $accessToken
        );
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
}

// Debug helper
function printr($arr) {
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}
