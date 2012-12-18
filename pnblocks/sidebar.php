<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Michael Bhola
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: sidebar.php 81 2009-08-19 02:48:25Z ph $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(str_replace('pnblocks', 'g2helper.php', dirname(__FILE__)));

/**
 * Initialise block
 */
function mGallery2_sidebarblock_init()
{
    // Security scheme
    SecurityUtil::registerPermissionSchema('mGallery2:Sidebarblock:', 'Block title::');
}

/**
 * Get information of the block
 * 
 * @return       array       The block information
 */
function mGallery2_sidebarblock_info()
{
    return array('text_type'      => 'Sidebar',
                 'module'         => 'mGallery2',
                 'text_type_long' => _MG2BLOCKSIDEBAR,
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
function mGallery2_sidebarblock_display($blockinfo)
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2:Sidebarblock:', $blockinfo['title'].'::', ACCESS_READ)) {
        return false;
    }

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Check if the mGallery2 module is available. 
// quick fix to display sidebar
//    if (!pnModAvailable('mGallery2') || pnModGetName() != 'mGallery2' || FormUtil::getPassedValue('type') != 'user') {
    if (!pnModAvailable('mGallery2') || pnModGetName() != 'mGallery2' || FormUtil::getPassedValue('type') == 'admin') {
        return false;
    }

    if (!pnModGetVar('mGallery2', 'sideblock')) {
        return;
    }

    // Create output object
    $pnRender = pnRender::getInstance('mGallery2');

    $result = mGallery2_helper_init();
    !$result['success'] || $result = mGallery2_helper_handle_request();
    if (!$result['success']) {
        LogUtil::registerError($result['error_message']);
        return;
    }

    $data = array();

    global $g2moddata;

    // sidebar block
    if (isset($g2moddata['sidebarBlocksHtml']) && !empty($g2moddata['sidebarBlocksHtml'])) {
        $data['sidebarHtml'] = '';
        foreach(array_keys($g2moddata['sidebarBlocksHtml']) as $key) {
            $data['sidebarHtml'] .= mGallery2_helper_convert_encoding($g2moddata['sidebarBlocksHtml'][$key]);
        }
        $pnRender->assign('sidebarHtml', $data['sidebarHtml']);
    } else {
        $pnRender->assign('g2moddata', $g2moddata);
    }

    $blockinfo['content'] = $pnRender->fetch('gallery2_block_sidebar.htm');

    return themesideblock($blockinfo);
}
