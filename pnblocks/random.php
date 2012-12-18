<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Michael Bhola
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: random.php 83 2009-08-19 06:11:07Z ph $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(str_replace('pnblocks', 'g2helper.php', dirname(__FILE__)));

/**
 * Initialise block
 */
function mGallery2_randomblock_init()
{
    // Security schema
    SecurityUtil::registerPermissionSchema('mGallery2:Randomblock:', 'Block title::');
}

/**
 * Get information of the block
 * 
 * @return       array       The block information
 */
function mGallery2_randomblock_info()
{
    return array('text_type'      => 'Random',
                 'module'         => 'mGallery2',
                 'text_type_long' => _MG2BLOCKRANDOM,
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
function mGallery2_randomblock_display($blockinfo)
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2:Randomblock:', $blockinfo['title'].'::', ACCESS_READ)) {
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
        $blockString = rtrim(str_repeat('randomImage|', $vars['numImages']), '|');
    } else {
        $blockString = 'randomImage';
    }
    if ($vars['albumId'] == 'all') {
        $vars['albumId'] = null;
    }

    list($ret, $data['bodyHtml'], $data['headHtml']) = GalleryEmbed::getImageBlock(array('blocks'       => $blockString,
                                                                                         'title'        => 'wubba',
                                                                                         $vars['sizeType'] => $vars['Size'],
                                                                                         'itemFrame'    => $vars['itemFrame'],
                                                                                         'albumFrame'   => $vars['albumFrame'],
                                                                                         'itemId'       => $vars['albumId'],
                                                                                         'show'         => implode('|', $show)));

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

    $blockinfo['content'] = $pnRender->fetch('gallery2_block_random.htm');

    return themesideblock($blockinfo);
}

/**
 * Block configuration update
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       array       $blockinfo     a blockinfo modified data
 */
function mGallery2_randomblock_modify($blockinfo)
{
    // Get the current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if(empty($vars['showTitle'])) {
        $vars['showTitle'] = 0;
    }

    if(empty($vars['showDate'])) {
        $vars['showDate'] = 0;
    }

    if(empty($vars['Size'])) {
        $vars['Size'] = 150;
    }

    if(empty($vars['showOwner'])) {
        $vars['showOwner'] = 0;
    }

    if(empty($vars['showViews'])) {
        $vars['showViews'] = 0;
    }

    if(empty($vars['itemFrame'])) {
        $vars['itemFrame'] = 'none';
    }

    if(empty($vars['albumFrame'])) {
        $vars['albumFrame'] = 'none';
    }

    if(empty($vars['numImages'])) {
        $vars['numImages'] = 1;
    }

    if(empty($vars['sizeType'])) {
        $vars['sizeType'] = 'maxSize';
    }

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
    }

    // album list for selector
    list ($ret1, $rootAlbumId) = GalleryCoreApi::getDefaultAlbumId();
    list ($ret2, $tree) = GalleryCoreApi::fetchAlbumTree();
    if ($ret1 || $ret2) {
        LogUtil::registerError(_MG2GETALBUMSELECTERROR);
    }
    if (empty($tree)) {
        $albums = array();
    } else {
        list ($ret3, $albums) = GalleryCoreApi::loadEntitiesById(GalleryUtilities::arrayKeysRecursive($tree), 'GalleryAlbumItem');
        if ($ret3) {
            LogUtil::registerError(_MG2GETALBUMSELECTERROR);
        }
    }
    $albumlist = array();
    $albumlist['all'] = _ALL;
    $depth = 0;
    foreach ($albums as $key => $album) {
        if ($album->getParentId() != $rootAlbumId) {
            $depth++;
        } else {
            $depth = 0;
        }
        $atitle = $album->getTitle() ? $album->getTitle() : $album->getPathComponent();
        $atitle = str_repeat('-- ', $depth) . $atitle;
        // truncate
        if (strlen($atitle) > 30) {
            $keep = 30 - strlen($atitle);
            $atitle = substr_replace($atitle, '...', $keep);
        }
        $aid = $album->getId();
        $albumlist[$aid] = $atitle;
    }
    $pnRender->assign('albumlist', $albumlist);
    

    // Add date, views, owner, etc.

    // Assign vars to the template
    $pnRender->assign(array(
        'showTitle'  => $vars['showTitle'],
        'showDate'   => $vars['showDate'],
        'Size'       => $vars['Size'],
        'sizeType'   => $vars['sizeType'],
        'sizeTypes'  => array('maxSize' => 'max', 'exactSize' => 'exact'),
        'showOwner'  => $vars['showOwner'],
        'showViews'  => $vars['showViews'],
        'itemFrame'  => $vars['itemFrame'],
        'albumFrame' => $vars['albumFrame'],
        'numImages'  => $vars['numImages'],
        'albumId'    => $vars['albumId']
    ));

    // Return the template
    return $pnRender->fetch('gallery2_block_random_modify.htm');
}

/**
 * Block configuration update
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       array       $blockinfo     a blockinfo modified data
 */
function mGallery2_randomblock_update($blockinfo)
{
    // Get the current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Update the values
    $vars['showTitle']  = FormUtil::getPassedValue('showTitle', 0, 'POST');
    $vars['showDate']   = FormUtil::getPassedValue('showDate', 0, 'POST');
    $vars['showOwner']  = FormUtil::getPassedValue('showOwner', 0, 'POST');
    $vars['showViews']  = FormUtil::getPassedValue('showViews', 0, 'POST');
    $vars['Size']       = FormUtil::getPassedValue('Size', 150, 'POST');
    $vars['sizeType']   = FormUtil::getPassedValue('sizeType', 'maxSize', 'POST');
    $vars['itemFrame']  = FormUtil::getPassedValue('itemFrame', 'none', 'POST');
    $vars['albumFrame'] = FormUtil::getPassedValue('albumFrame', 'none', 'POST');
    $vars['numImages']  = FormUtil::getPassedValue('numImages', 1, 'POST');
    $vars['albumId']    = FormUtil::getPassedValue('albumId', 'all', 'POST');

    // Write the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->clear_cache('gallery2_block_random.htm');

    return $blockinfo;
}
