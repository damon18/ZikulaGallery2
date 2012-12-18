<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Hinrich Donner
 * @author      Johnny Birchett
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: pnuserapi.php 88 2010-02-15 23:50:08Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    User model functions - all user database and process functions
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

class_exists('GalleryEmbed') || Loader::requireOnce(dirname(__FILE__).'/g2helper.php');

// Define some constants
define('MG2_FILTER_WORD', 1);
define('MG2_FILTER_INTEGER', 2);
define('MG2_FILTER_PIPED_STRING', 3);
define('MG2_TEMP_TAG', 'MG2(--0--)');

function mgallery2_filter_attr_value($text, $value_type = MG2_FILTER_WORD)
{
    // Strip off initial and final quotes
    $first = substr($text, 0, 1);
    if ($first == '"' || $first == ';') {
        if (substr($text, -1, 1) == $first) {
            $text = substr($text, 1, -1);
        }
    }
    switch($value_type) {
        case MG2_FILTER_WORD :
            return preg_replace("/\W/", '', $text);
        case MG2_FILTER_INTEGER :
            return preg_replace("/\D/", '', $text);
        case MG2_FILTER_PIPEDSTRING :
            return preg_replace("/[^\w|]/", '', $text);
        default :
            return check_plain($text);
    }
}

/**
 * The hook function
 */
function mGallery2_userapi_transform($args)
{
    // Argument check
    if (!isset($args['objectid']) || !isset($args['extrainfo'])) {
        return LogUtil::registerError(_MG2ARGSERROR);
    }

    if (is_array($args['extrainfo'])) {
        foreach ($args['extrainfo'] as $text) {
            $result[] = mGallery2_transform($text);
        }
    } else {
        $result = mGallery2_transform($text);
    }

    return $result;
}

/**
 * Main transformation function
 */
function mGallery2_transform($text)
{
    // check the user agent - if it is a bot, return immediately
    $robotslist = array('ia_archiver',
                        'googlebot',
                        'mediapartners-google',
                        'yahoo!',
                        'msnbot',
                        'jeeves',
                        'lycos');
    $useragent = pnServerGetVar('HTTP_USER_AGENT');
    for($cnt = 0; $cnt < count($robotslist); $cnt++) {
        if(strpos(strtolower($useragent), $robotslist[$cnt]) !== false) {
            return $text;
        }
    }

    // Find all of the codes and loop over them. Replace each with an image block
    $matchtext = "/\[+mg2:(\d+)(\s*,)?\s*(.*?)\]/i";
    preg_match_all($matchtext, $text, $matches, PREG_SET_ORDER);

    // If there is at least one match, do it
    if (count($matches) > 0) {
        // Get defaults
        $default_size = (pnModGetVar('mGallery2', 'maxSize')) ? pnModGetVar('mGallery2', 'maxSize') : '150';
        $default_div_class = 'nowrap';
        $default_album_frame = (pnModGetVar('mGallery2', 'albumFrame')) ? pnModGetVar('mGallery2', 'albumFrame') : 'none';
        $default_item_frame = (pnModGetVar('mGallery2', 'itemFrame')) ? pnModGetVar('mGallery2', 'itemFrame') : 'none';
        $default_block_type = 'specificImage';
        $default_n_images = '1';
        $default_show = mgallery2_get_show();
        
        // Hold the list of frames used for images. Add the CSS at the end
        $frame_list = array();
        
        // Initialize Gallery2
        mGallery2_helper_init();
    }

    foreach ($matches as $match) {
        // skip any tags with double braces at the beginning
        if (substr($match[0], 0, 2) == "[[") {
            $text = str_replace($match[0], MG2_TEMP_TAG . substr($match[0], 2), $text);
        } else {
            // Pull out the args in the code
            $args = array();
            preg_match_all("/(\w+)\=(\"[^\"]*\"|\S*)/", $match[3], $a, PREG_SET_ORDER);

            foreach ($a as $arg) {
                $args[strtolower($arg[1])] = $arg[2];
            }

            // Set number of images to show
            $n_images = mgallery2_filter_attr_value($args['n'], MG2_FILTER_INTEGER);
            if($n_images == 0) {
                // No size specified; use default
                $n_images = $default_n_images;
            }

            // Set the block type
            $block_type = mgallery2_filter_attr_value($args['type'], MG2_FILTER_WORD);
            if(empty($block_type)) {
                // No block type specified; use default
                $block_type = $default_block_type;
            }

            /*
            if ($n_images <=1) {
                $block_type = 'specificItem'; // This will show something besides an error if n=1 and an album is selected
            }
            */

            // set the size of the thumbnail
            $size = mgallery2_filter_attr_value($args['size'], MG2_FILTER_INTEGER);
            if ($size == 0) {
                // No size specified; use default
                $size = $default_size;
            }

            // Set the show var
            $show = mgallery2_filter_attr_value($args['show'], MG2_FILTER_PIPEDSTRING);
            if(empty($show)) {
                // No show specified; use default
                $show = $default_show;
            } else {
                $show = explode('|', $show);
            }

            // Set the class of the div
            $div_class = mgallery2_filter_attr_value($args['class'], MG2_FILTER_WORD);
            if(empty($div_class)) {
                // No class specified; use default
                $div_class = $default_div_class;
            }

            // switch the class to g2image versions
            switch($div_class) {
                case 'left':
                    $div_class = 'g2image_float_left';
                    break;
                case 'right':
                    $div_class = 'g2image_float_right';
                    break;
                case 'center':
                case 'centre':
                    $div_class = 'g2image_centered';
                    break;
                case 'normal':
                    $div_class = 'g2image_normal';
                    break;
                case 'left_inline':
                    $div_class = 'g2image_float_left_inline';
                    break;
                case 'right_inline':
                    $div_class = 'g2image_float_right_inline';
                    break;
                case 'center_inline':
                case 'centre_inline':
                    $div_class = 'g2image_centered_inline';
                    break;
            }

            // Set the album and item frames
            $frame = mgallery2_filter_attr_value($args['frame'], MG2_FILTER_WORD);
            $album_frame = mgallery2_filter_attr_value($args['aframe'], MG2_FILTER_WORD);
            $item_frame = mgallery2_filter_attr_value($args['iframe'], MG2_FILTER_WORD);

            if(empty($frame)) {
                // No overriding frame given; check for album and item frames
                if(empty($album_frame)) {
                    // No album frame given; use default
                    $album_frame = $default_album_frame;
                }

                if(empty($item_frame)) {
                    // No item frame specified; use default
                    $item_frame = $default_item_frame;
                }
            } else {
                // Overriding frame given; use it
                $album_frame = $frame;
                $item_frame = $frame;
            }

            // Add the requested frames to the array so we can get the CSS later. Dupes will be filtered out later
            array_push($frame_list, $frame);
            array_push($frame_list, $album_frame);
            array_push($frame_list, $item_frame);

            // This part fetches the image block
            $param_blocks_array = array_fill(0, $n_images, $block_type);
            $params['itemId'] = $match[1];
            if($params['itemId'] == '0') unset($params['itemId']);
            $params['blocks'] = is_array($param_blocks_array) ? implode('|', $param_blocks_array) : "";
            $param_show_array = $show;
            $params['show'] = is_array($param_show_array) ? implode('|', $param_show_array) : "";
            $params['maxSize'] = $size;
            $params['albumFrame'] = $album_frame;
            $params['itemFrame'] = $item_frame;

            $g2_head = array();
            $block = array();
            if (!class_exists('GalleryEmbed')) {
                $text = str_replace($match[0], '<span style="color:red">'._MG2GALLERYCONFIGERROR.'</span>', $text);
                continue;
            }
            list ($ret, $content, $head) = GalleryEmbed::getImageBlock($params);
            if ($ret) {
                $text = str_replace($match[0], '<div style="color:red">'._MG2GALLERYINITERROR.':<br />'.$ret->GetAsHTML().'</div>', $text);
            } else {
                if ($content) {
                    // Add a div around the content for styling
                    if ($div_class != 'none') {
                        $content = '<div class="giImageBlock ' . $div_class . '">' . $content . '</div>';
                    }
                    // This puts the image block HTML back into the rest of the text
                    // inline the tables
                    // $content = preg_replace('/table/', 'table style="display:inline;" ', $content);
                    $text = str_replace($match[0], $content, $text);
                } else {
                    // There was no content. This usually happens when an album-type block is selected with an image id specified
                    // Change this to try loading the block without an itemId
                    $text = str_replace($match[0], '<span style="color:red">'._MG2GALLERYIMAGEERROR.'</span>', $text);
                }
                if (head) {
                    $g2_head[] = $head;
                }
            }
        }
    }
    // End of for loop through matches

    // Replace original escape tag prefix
    $text = str_replace(MG2_TEMP_TAG, '[[', $text);
    // If we had at least one match, finish up by adding the css. Unfortunately, if there are multiple images on a page, this will get added
    // multiple times.
    if (count($matches) > 0) {
        if (class_exists('GalleryEmbed')) {
            GalleryEmbed::done();
        }
        if ($g2_head) {
            // Add header info for CSS
            global $additional_header;
            $additional_header[] = implode("\n", array_unique($g2_head));
        }
        // Add css for div wrapper - do something better than this
        PageUtil::addVar('stylesheet', 'modules/mGallery2/pnstyle/style.css');
        $text .= '<br class="giImageBlock-clear-both" />';
    }
    return $text;
}

function mgallery2_get_show()
{
    // reset return var
    $show = array();

    // check the module variables to build the "show" options
    if (pnModGetVar('mGallery2', 'showTitle')) {
        $show[] = 'title';
    }

    if (pnModGetVar('mGallery2', 'showOwner')) {
        $show[] = 'owner';
    }

    if (pnModGetVar('mGallery2', 'showDate')) {
        $show[] = 'date';
    }

    if (pnModGetVar('mGallery2', 'fullSize')) {
        $show[] = 'fullSize';
    }

    if (pnModGetVar('mGallery2', 'showViews')) {
        $show[] = 'views';
    }

    return $show;
}


/**
 * decode the custom url string
 *
 * @author Mateo Tibaquirá
 * @return bool true if successful, false otherwise
 */
function mGallery2_userapi_decodeurl($args)
{
    // check we actually have some vars to work with...
    if (!isset($args['vars'])) {
        return LogUtil::registerError (_MODARGSERROR);
    }

    // some servers doesn't decode the additional parameters of Gallery
    // so we process the REQUEST URI to set the g2 vars
    $request = pnServerGetVar('REQUEST_URI');
    $request = substr($request, strpos($request, '?')+1);

    // extract and set the parameters
    $request = explode('&', $request);
    foreach ($request as $parameter) {
        $parameter = explode('=', $parameter);
        pnQueryStringSetVar($parameter[0], $parameter[1]);
    }

    return true;
}
