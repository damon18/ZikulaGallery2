<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Mateo Tibaquira
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: erandom.php 72 2008-12-17 04:06:29Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

/**
 * Initialise block
 */
function mGallery2_erandomblock_init()
{
    // Security schema
    SecurityUtil::registerPermissionSchema('mGallery2:erandomblock:', 'Block id::');
}

/**
 * Get information of the block
 *
 * @return       array       The block information
 */
function mGallery2_erandomblock_info()
{
    return array('text_type'      => 'erandom',
                 'module'         => 'mGallery2',
                 'text_type_long' => 'erandom',
                 'allow_multiple' => true,
                 'form_content'   => true,
                 'form_refresh'   => false,
                 'show_preview'   => true);
}

/**
 * Display block
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered block
 */
function mGallery2_erandomblock_display($blockinfo)
{
    if (!SecurityUtil::checkPermission('mGallery2:erandomblock:', $blockinfo['bid'].'::', ACCESS_OVERVIEW)) {
       return;
    }

    // Check if the mGallery2 module is available. 
    if (!pnModAvailable('mGallery2')) {
        return false;
    }

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (!isset($vars['maxSize']) || empty($vars['maxSize'])) {
        $vars['maxSize'] = 300;
    }

    // Reset the template data
    $data = array();

    // Get the paths
    $baseurl = substr(pnGetBaseURL(), 0, -1);
    $g2uri   = pnModGetVar('mGallery2', 'g2uri');

    // Start the output buffer and capture the image
    ob_start();
    @readfile($baseurl.$g2uri.'/main.php?g2_view=imageblock.External&g2_maxSize='.$vars['maxSize'].'&link');
    $data['bodyHtml'] = ob_get_contents();
    ob_end_clean();

    if (empty($data['bodyHtml'])) {
        LogUtil::registerError(_MG2BLOCKERROR);
        return;
    }

    // Create output object and render the block
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->assign('bodyHtml', $data['bodyHtml']);

    $blockinfo['content'] = $pnRender->fetch('gallery2_block_erandom.htm');

    return pnBlockThemeBlock($blockinfo);
}

/**
 * Block configuration form
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered block configuration form
 */
function mGallery2_erandomblock_modify($blockinfo)
{
    // Get the current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['maxSize'])) {
        $vars['maxSize'] = 300;
    }
    
    // Create the output object
    $pnRender = pnRender::getInstance('mGallery2', false);
    $pnRender->assign('maxSize', $vars['maxSize']);

    // Return the template
    return $pnRender->fetch('gallery2_block_erandom_modify.htm');
}

/**
 * Block configuration update
 *
 * @param        array       $blockinfo     a blockinfo structure
 * @return       array       $blockinfo     a blockinfo modified data
 */
function mGallery2_erandomblock_update($blockinfo)
{
    // Get the current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Update the values
    $vars['maxSize'] = FormUtil::getPassedValue('maxSize', 300, 'POST');

    // Write the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    return $blockinfo;
}
