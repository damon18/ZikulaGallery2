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
 * @version     $Id: pnuser.php 88 2010-02-15 23:50:08Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    User display functions - This file contains all user GUI functions
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

class_exists('GalleryEmbed') || Loader::requireOnce(dirname(__FILE__).'/g2helper.php');

/**
 * the main user function
 * 
 * @return       output       The main module page
 */
function mGallery2_user_main()
{
    if (!SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    // Error flag
    $error = false;
    
    // Create output object
    $pnRender = pnRender::getInstance('mGallery2');

    // Process G2 and enable the flag if there was an error
    $result = mGallery2_helper_init();
    !$result['success'] || $result = mGallery2_helper_handle_request();
    if (!$result['success']) {
        return Logutil::registerError($result['error_message'], 404);
    }

    $data = array();

    global $g2moddata;

    // put the body html from G2 into the zikula template 
    $data['bodyHtml'] = isset($g2moddata['bodyHtml']) ? mGallery2_helper_convert_encoding($g2moddata['bodyHtml']) : '';

    // get the page title, javascript and css links from the <head> html from G2
    $title = '';
    $javascript = array();
    $css = array();

    if (isset($g2moddata['headHtml'])) {
        list($data['title'], $css, $javascript) = GalleryEmbed::parseHead(mGallery2_helper_convert_encoding($g2moddata['headHtml']));
    }

    // Add G2 javascript
    $data['javascript'] = '';
    if (!empty($javascript)) {
        foreach ($javascript as $script) {
            $data['javascript'] .= "\n".$script;
        }
    }

    // Add G2 css
    $data['css'] = '';
    if (!empty($css)) {
        foreach ($css as $style) {
            $data['css'] .= "\n".$style;
        }
    }

    PageUtil::setVar('title', $data['title']);

    $pnRender->assign('bodyHtml',   $data['bodyHtml']);
    $pnRender->assign('javascript', $data['javascript']);
    $pnRender->assign('css',        $data['css']);

    // Return the generated output
    return $pnRender->fetch('gallery2_user_main.htm');
}
