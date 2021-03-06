<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Michael Bhola
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: specific.php 72 2008-12-17 04:06:29Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(str_replace('pnblocks', 'g2helper.php', dirname(__FILE__)));

/**
 * initialise block
 */
function mGallery2_specificblock_init()
{
    // Security scheme
    SecurityUtil::registerPermissionSchema('mGallery2:Specificblock:', 'Block title::');
}

/**
 * Get information of the block
 * 
 * @return       array       The block information
 */
function mGallery2_specificblock_info()
{
    return array('text_type'      => 'Specific',
                 'module'         => 'mGallery2',
                 'text_type_long' => _MG2BLOCKSPECIFIC,
                 'allow_multiple' => true,
                 'form_content'   => false,
                 'form_refresh'   => false,
                 'show_preview'   => true);
}

/**
 * display block
 * 
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered block
 */
function mGallery2_specificblock_display($blockinfo)
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2:Specificblock:', $blockinfo['title'].'::', ACCESS_READ)) {
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

    $itemId = GalleryCoreApi::fetchItemIdByPath($vars['imagepath']);
    list($ret, $data['bodyHtml'], $data['headHtml']) = GalleryEmbed::getImageBlock(array('blocks' => 'specificItem',
                                                                                         'itemId' => $itemId[1],
                                                                                         'show' => 'title|date'));
    if ($ret) {
        LogUtil::registerError($ret->getAsHtml());
        return;
    }

    $data['bodyHtml'] = mGallery2_helper_convert_encoding($data['bodyHtml']);
    $data['headHtml'] = mGallery2_helper_convert_encoding($data['headHtml']);

    // Create output object and render the block
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->assign('bodyHtml', $data['bodyHtml']);
    $pnRender->assign('headHtml', $data['headHtml']);

    $blockinfo['content'] = $pnRender->fetch('gallery2_block_specific.htm');

    return themesideblock($blockinfo);
}

/**
 * modify block settings
 * 
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the bock form
 */
function mGallery2_specificblock_modify($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Create output object
    $pnRender = pnRender::getInstance('mGallery2', false);

    // assign the approriate values
    $pnRender->assign('imagepath', $vars['imagepath']);

    // Return the output that has been generated by this function
    return $pnRender->fetch('gallery2_block_specific_modify.htm');
}

/**
 * Block configuration update
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       array       $blockinfo     a blockinfo modified data
 */
function mGallery2_specificblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // alter the corresponding variable
    $vars['imagepath'] = FormUtil::getPassedValue('imagepath');

    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->clear_cache('gallery2_block_specific.htm');

    return $blockinfo;
}
