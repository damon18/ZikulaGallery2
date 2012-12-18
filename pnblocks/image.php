<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Johnny Birchett
 * @author      Michael Bhola
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: image.php 72 2008-12-17 04:06:29Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(str_replace('pnblocks', 'g2helper.php', dirname(__FILE__)));

/**
 * Initialise block
 */
function mGallery2_imageblock_init()
{
    // Security schema
    SecurityUtil::registerPermissionSchema('mGallery2:Imageblock:', 'Block title::');
}

/**
 * Get information of the block
 * 
 * @return       array       The block information
 */
function mGallery2_imageblock_info()
{
    return array('text_type'      => 'Image',
                 'module'         => 'mGallery2',
                 'text_type_long' => _MG2BLOCKIMAGE,
                 'allow_multiple' => true,
                 'form_content'   => false,
                 'form_refresh'   => false,
                 'show_preview'   => true);
}

/**
 * Display block
 * 
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered block
 */
function mGallery2_imageblock_display($blockinfo)
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2:Imageblock:', $blockinfo['title'].'::', ACCESS_READ)) {
        return false;
    }

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Check if the mGallery2 module is available. 
    if (!pnModAvailable('mGallery2')) {
        return false;
    }

    $result = mGallery2_helper_init();
    if (!$result['success']) {
        LogUtil::registerError($result['error_message']);
        return;
    }

    $data = array();

    // The configuration for the 'show' parameter
    $show = array();
    if ($vars['showTitle']) {
        $show[] = 'title';
    }
    if ($vars['showOwner']) {
        $show[] = 'owner';
    }
    if ($vars['showViews']) {
        $show[] = 'views';
    }
    if($vars['showDate']) {
        $show[] = 'date';
    }

    if ($vars['numImages'] && $vars['numImages'] > 0) {
        $blockString = rtrim(str_repeat($vars['blockType'].'|', $vars['numImages']), '|');
    } else {
        $blockString = $vars['blockType'];
    }

    // If there is no itemid
    if (!$vars['itemId'] || $vars['itemId'] == 0 || empty($vars['itemId'])) {

        // Get the root album Id
        list ($ret, $rootAlbumId) = GalleryCoreApi::getDefaultAlbumId();

        // Check for errors
        if ($ret) {
            LogUtil::registerError($ret->getAsHtml());
            return;
        }

        // We have the root album ID. Set the itemId to the root
        $vars['itemId'] = $rootAlbumId;

    }

    // There is an item id, is it an album or an image?
    list ($ret, $item) = GalleryCoreApi::loadEntitiesById($vars['itemId'], 'GalleryItem');

    // if there's a Gallery2 error or
    // if there is no $item object, the itemId was bogus
    if ($ret || !$item || $item == NULL || empty($item)) {
        LogUtil::registerError(_MG2ITEMNOTEXIST.' '.$vars['itemId']);
        return;
    }

    // If there is an item, make sure it's an album or a photo
    $type = $item->getEntityType();
    if ($type != 'GalleryPhotoItem' && $type != 'GalleryAlbumItem') {
        LogUtil::registerError(pnML('_MG2WRONGTYPE', array('type' => $type, 'blockType' => $vars['blockType'])).' '._MG2ITEMID.': '.$vars['itemId']);
        return;
    }

    // If the block is an album-type block, make sure the itemId is an album
    if ($vars['blockType'] == 'randomImage' || $vars['blockType'] == 'randomAlbum') {
        if ($vars['itemId'] == NULL) {
            LogUtil::registerError(_MG2ALBUMTYPEERROR);
            return;
        } else {
            // There was an itemId. Check the type of G2 entity to make sure it's not a photo
            if ($type != 'GalleryAlbumItem') {
                LogUtil::registerError(_MG2ALBUMTYPEERROR.' '._MG2ITEMID.': '.$vars['itemId']);
                return;
            }
        }
    }

    list($ret, $data['bodyHtml'], $data['headHtml']) = GalleryEmbed::getImageBlock(array('blocks' => $blockString,
                                                                                         'itemId' => $vars['itemId'],
                                                                                         'title' => 'wubba',
                                                                                         'maxSize' => $vars['maxSize'],
                                                                                         'itemFrame' => $vars['itemFrame'],
                                                                                         'albumFrame' => $vars['albumFrame'],
                                                                                         'show' => implode('|', $show)));
    if ($ret) {
        LogUtil::registerError($ret->getAsHtml());
        return;
    }
    $data['bodyHtml'] = mGallery2_helper_convert_encoding($data['bodyHtml']);
    $data['headHtml'] = mGallery2_helper_convert_encoding($data['headHtml']);

    // If there is no body html, that means the block was configured incorrectly
    if (empty($data['bodyHtml'])) {
        LogUtil::registerError(_MG2BLOCKERROR);
        return;
    }

    // Create output object and render the block
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->assign('bodyHtml', $data['bodyHtml']);
    $pnRender->assign('headHtml', $data['headHtml']);

    $blockinfo['content'] = $pnRender->fetch('gallery2_block_image.htm');

    return themesideblock($blockinfo);
}

/**
 * Block configuration form
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered block configuration form
 */
function mGallery2_imageblock_modify($blockinfo)
{
    // Get the current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['showTitle'])) {
        $vars['showTitle'] = 0;
    }

    if (empty($vars['showDate'])) {
        $vars['showDate'] = 0;
    }

    if (empty($vars['maxSize'])) {
        $vars['maxSize'] = 150;
    }

    if (empty($vars['showOwner'])) {
        $vars['showOwner'] = 0;
    }

    if (empty($vars['showViews'])) {
        $vars['showViews'] = 0;
    }

    if (empty($vars['itemFrame'])) {
        $vars['itemFrame'] = 'none';
    }

    if (empty($vars['albumFrame'])) {
        $vars['albumFrame'] = 'none';
    }

    if (empty($vars['numImages'])) {
        $vars['numImages'] = 1;
    }

    if (empty($vars['itemId'])) {
        $vars['itemId'] = 0;
    }

    $block_types = array(
                         'specificImage' => 'Specific Image',
                         'randomImage'   => 'Random Image',
                         'recentImage'   => 'Recent Image',
                         'viewedImage'   => 'Most Viewed Image',
                         'dailyImage'    => 'Daily Image',
                         'weeklyImage'   => 'Weekly Image',
                         'monthlyImage'  => 'Monthly Image',
                         'randomAlbum'   => 'Random Album',
                         'recentAlbum'   => 'Recent Album',
                         'viewedAlbum'   => 'Most Viewed Album',
                         'dailyAlbum'    => 'Daily Album',
                         'weeklyAlbum'   => 'Weekly Album',
                         'monthlyAlbum'  => 'Monthly Album'
                        );

    // Create the output object
    $pnRender = pnRender::getInstance('mGallery2', false);

    // Init G2
    $result = mGallery2_helper_init();
    if (!$result['success']) {
        LogUtil::registerError($result['error_message']);
        return;
    }

    // Get the list of imageframes
    list ($ret, $imageframe) = GalleryCoreApi::newFactoryInstance('ImageFrameInterface_1_1');
    if ($ret) {
        LogUtil::registerError(_MG2IMAGEFRAMEINITERROR);
    }
    if (isset($imageframe)) {
        list ($ret, $list) = $imageframe->getImageFrameList();
        if ($ret) {
            LogUtil::registerError($ret->wrap(__FILE__, __LINE__));
        }

        list ($ret, $sampleUrl) = $imageframe->getSampleUrl($itemId);
        if ($ret) {
            LogUtil::registerError($ret->wrap(__FILE__, __LINE__));
        }

        $pnRender->assign('sample', $sampleUrl);
        $pnRender->assign('frames', $list);
        $pnRender->assign('block_types', $block_types);
    }

    // Add date, views, owner, etc.

    // Assign vars to the template
    $pnRender->assign(array(
        'showTitle'  => $vars['showTitle'],
        'showDate'   => $vars['showDate'],
        'maxSize'    => $vars['maxSize'],
        'showOwner'  => $vars['showOwner'],
        'showViews'  => $vars['showViews'],
        'itemFrame'  => $vars['itemFrame'],
        'albumFrame' => $vars['albumFrame'],
        'numImages'  => $vars['numImages'],
        'blockType'  => $vars['blockType'],
        'itemId'     => $vars['itemId']
    ));

    // Return the template
    return $pnRender->fetch('gallery2_block_image_modify.htm');
}

/**
 * Block configuration update
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       array       $blockinfo     a blockinfo modified data
 */
function mGallery2_imageblock_update($blockinfo)
{
    // Get the current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Update the values
    $vars['showTitle']  = FormUtil::getPassedValue('showTitle', 0, 'POST');
    $vars['showDate']   = FormUtil::getPassedValue('showDate', 0, 'POST');
    $vars['showOwner']  = FormUtil::getPassedValue('showOwner', 0, 'POST');
    $vars['showViews']  = FormUtil::getPassedValue('showViews', 0, 'POST');
    $vars['maxSize']    = FormUtil::getPassedValue('maxSize', 150, 'POST');
    $vars['itemFrame']  = FormUtil::getPassedValue('itemFrame', 'none', 'POST');
    $vars['albumFrame'] = FormUtil::getPassedValue('albumFrame', 'none', 'POST');
    $vars['numImages']  = FormUtil::getPassedValue('numImages', 1, 'POST');
    $vars['itemId']     = FormUtil::getPassedValue('itemId', 0, 'POST');
    $vars['blockType']  = FormUtil::getPassedValue('blockType');

    // Write the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->clear_cache('gallery2_block_image.htm');

    return $blockinfo;
}

function parse_imageblock($imageblock)
{
    // empty function?
}
