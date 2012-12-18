<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Hinrich Donner
 * @author      Johnny Birchett
 * @author      Michael Bhola
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: pnadmin.php 86 2009-10-29 15:39:14Z ph $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    Admin display functions - This file contains all administrative GUI functions
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(dirname(__FILE__).'/g2helper.php');

/**
 * the main administration function
 *
 * @return       output       The main module admin page.
 */
function mGallery2_admin_main()
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('mGallery2');

    // Return the generated output
    return $pnRender->fetch('gallery2_admin_main.htm');
}

/**
 * Modify configuration
 *
 * @return       output       The configuration page
 */
function mGallery2_admin_modifyconfig()
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('mGallery2', false);

    // assign default values
    $config = pnModGetVar('mGallery2');
    $pnRender->assign($config);

    // Initialize G2
    $result = mGallery2_helper_init();
    if ($result['success']) {
        
        // Get the status of the Gallery plugins
        $g2_image_block_active = 0;
        $g2_moduleid = 'imageblock';
        list ($ret, $g2_modulestatus) = GalleryCoreApi::fetchPluginStatus('module');
        if (!$ret) {
            $image_block = ((isset($g2_modulestatus['imageblock']) && !empty($g2_modulestatus['imageblock']['active']) && $g2_modulestatus['imageblock']['active']) ? 1 : 0);
            $image_frame = ((isset($g2_modulestatus['imageframe']) && !empty($g2_modulestatus['imageframe']['active']) && $g2_modulestatus['imageframe']['active']) ? 1 : 0);
            $user_album = ((isset($g2_modulestatus['useralbum']) && !empty($g2_modulestatus['useralbum']['active']) && $g2_modulestatus['useralbum']['active']) ? 1 : 0);
        } else {
            LogUtil::registerError($ret->getAsHtml());
        }

        if ($user_album) {
            list ($ret, $useralbum_opt) = GalleryCoreApi::getPluginParameter('module', 'useralbum', 'create');
            // 'access' or 'immediate'
            if (!$ret) {
                $pnRender->assign('useralbum_opt', $useralbum_opt);
            } else {
                LogUtil::registerError($ret->getAsHtml());
            }
        }


/*        // Get the status of the Gallery imageblock plugin
        $g2_image_block_active = 0;
        $g2_moduleid = 'imageblock';
        list ($ret, $imageblock_status ) = GalleryCoreApi::fetchPluginStatus('module');
        if (!$ret) {
            $image_block = ((isset($imageblock_status[$g2_moduleid]) && !empty($imageblock_status[$g2_moduleid]['active']) && $imageblock_status[$g2_moduleid]['active']) ? 1 : 0);
        } else {
            $image_block_error = $ret->getAsHtml();
        }

        // Get the status of the Gallery imageframe plugin
        $g2_image_block_active = 0;
        $g2_moduleid = 'imageframe';
        list ($ret, $g2_modulestatus ) = GalleryCoreApi::fetchPluginStatus('module');
        if(!$ret) {
            $image_frame = ((isset($g2_modulestatus[$g2_moduleid]) && !empty($g2_modulestatus[$g2_moduleid]['active']) && $g2_modulestatus[$g2_moduleid]['active']) ? 1 : 0);
        } else {
            $image_frame_error = $ret->getAsHtml();
        }
*/
        $pnRender->assign(array('image_block'       => $image_block,
//                                'image_block_error' => (($image_block_error) ? $image_block_error : ''),
                                'image_frame'       => $image_frame,
//                                'image_frame_error' => (($image_frame_error) ? $image_frame_error : '')
                          ));

        // Get the list of imageframes
        list ($ret, $imageframe) = GalleryCoreApi::newFactoryInstance('ImageFrameInterface_1_1');
        if ($ret) {
            $mg2_error = _MG2IMAGEFRAMEINITERROR;
        }
        if (isset($imageframe)) {
            list ($ret, $list) = $imageframe->getImageFrameList();
            if ($ret) {
                $mg2_error =  array($ret->wrap(__FILE__, __LINE__), null);
            }

            list ($ret, $sampleUrl) = $imageframe->getSampleUrl($itemId);
            if ($ret) {
                $mg2_error = array($ret->wrap(__FILE__, __LINE__), null);
            }

            $pnRender->assign('sample', $sampleUrl);
            $pnRender->assign('frames', $list);
        }

        // get g2 theme list
        $g2themelist = array();
        list ($ret, $g2_themestatus) = GalleryCoreApi::fetchPluginStatus('theme');
        if(!$ret) {
            foreach (array_keys($g2_themestatus) as $g2theme) {
                if (isset($g2_themestatus[$g2theme]['active']) &&
                   ($g2_themestatus[$g2theme]['active'] == 1) &&
                   ($g2_themestatus[$g2theme]['available'] == 1)) {

                    $g2themelist[] = $g2theme;
                }
            }
        }
        // g2 default theme
        list ($ret, $defaultThemeId) = GalleryCoreApi::getPluginParameter('module', 'core', 'default.theme');
        if (!$ret) {
            $g2deftheme = $defaultThemeId;
        } else {
            $g2deftheme = '';
        }

        $pnRender->assign('g2themelist', $g2themelist);
        $pnRender->assign('g2deftheme', $g2deftheme);

        // get all zikula groups
        $zikulagroups = pnModAPIFunc('Groups', 'user', 'getall');
        $zkgroups = array();
        foreach ($zikulagroups as $zkgarray) {
            $zkgroups[$zkgarray['gid']] = $zkgarray['name'];
        }
        // get all G2 groups
        list ($re, $gallery2groups) = GalleryCoreApi::fetchGroupNames();
        if (!$re) {
            $g2groups = array();
            foreach ($gallery2groups as $g2gid => $g2gname) {
                // excluding group ids 2,4
                if (($g2gid != 2) &&
                    ($g2gid != 4)) {
                    $g2groups[$g2gid] = $g2gname;
#                    echo "gid: $g2gid , gname: $g2gname | ";
                }
            }
        }
// start test bed


// end test bed
        $pnRender->assign('g2groups', $g2groups);
        $pnRender->assign('zkgroups', $zkgroups);
        $pnRender->assign('g2group2', explode(",", $config['g2group']));
        $pnRender->assign('zkgroup2', explode(",", $config['zkgroup']));

    } else { // Couldn't init G2
        $mg2_error = _MG2GALLERYINITERROR;
    }

    // Assign vars to template
//    $pnRender->assign(pnModGetVar('mGallery2'));

    // Return the generated output
    return $pnRender->fetch('gallery2_admin_modifyconfig.htm');
}



/**
 * Update the configuration
 *
 */
function mGallery2_admin_updateconfig()
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters from whatever input we need
    $basedirectory  = FormUtil::getPassedValue('basedirectory');
    $g2uri          = FormUtil::getPassedValue('g2uri');
    $synchronize    = FormUtil::getPassedValue('synchronize');
    $syncusers      = FormUtil::getPassedValue('syncusers');
    $zkgroup        = FormUtil::getPassedValue('zkgroup');
    $syncgroups     = FormUtil::getPassedValue('syncgroups');
    $g2group        = FormUtil::getPassedValue('g2group');
    $skipemptygroup = FormUtil::getPassedValue('skipemptygroup');
    $g2group_opts   = FormUtil::getPassedValue('g2group_opts');
    $supportsha     = FormUtil::getPassedValue('supportsha');
    $sideblock      = FormUtil::getPassedValue('sideblock');
    $showTitle      = FormUtil::getPassedValue('showTitle');
    $showOwner      = FormUtil::getPassedValue('showOwner');
    $showDate       = FormUtil::getPassedValue('showDate');
    $showViews      = FormUtil::getPassedValue('showViews');
    $fullSize       = FormUtil::getPassedValue('fullSize');
    $maxSize        = FormUtil::getPassedValue('maxSize');
    $albumFrame     = FormUtil::getPassedValue('albumFrame');
    $itemFrame      = FormUtil::getPassedValue('itemFrame');
    $g2embtheme     = FormUtil::getPassedValue('g2embtheme');
    $g2overridetheme = FormUtil::getPassedValue('g2overridetheme');

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('mGallery2', 'admin', 'modifyconfig'));
    }

    $g2group_var = implode(",", $g2group);
    $zkgroup_var = implode(",", $zkgroup);

    // Update module variables
    pnModSetVar('mGallery2', 'basedirectory', $basedirectory);
    pnModSetVar('mGallery2', 'g2uri', $g2uri);
    pnModSetVar('mGallery2', 'sideblock', $sideblock);
    pnModSetVar('mGallery2', 'fullSize', $fullSize); // show image full size?
    pnModSetVar('mGallery2', 'maxSize', $maxSize); // (closest) maximum size for fullsize images
    pnModSetVar('mGallery2', 'showTitle', $showTitle); // show title with image?
    pnModSetVar('mGallery2', 'showDate', $showDate); // show image date?
    pnModSetVar('mGallery2', 'showViews', $showViews); // show number of views?
    pnModSetVar('mGallery2', 'showOwner', $showOwner); // show the image owner?
    pnModSetVar('mGallery2', 'albumFrame', $albumFrame); // what frame to use with albums
    pnModSetVar('mGallery2', 'itemFrame', $itemFrame); // what frame to use with images
    // synchronisation
    pnModSetVar('mGallery2', 'synchronize', $synchronize); // enable synchronization (bolean)
    pnModSetVar('mGallery2', 'syncusers', $syncusers); // values: all,group
    pnModSetVar('mGallery2', 'syncgroups', $syncgroups); // values: all,none,group
    pnModSetVar('mGallery2', 'g2group', $g2group_var); // add users to g2 group(s)
    pnModSetVar('mGallery2', 'zkgroup', $zkgroup_var); // only sync members of zikula group(s)
    pnModSetVar('mGallery2', 'g2group_opts', $g2group_opts); // opt1: put user in all groups / opt1: put user in groups he is member of only
    pnModSetVar('mGallery2', 'skipemptygroup', $skipemptygroup); // disregard groups with no members
    pnModSetVar('mGallery2', 'supportsha', $supportsha); // does g2 support sha1/256 hashmethods?
    // what theme to use in embedded mode
    if ($g2overridetheme == 1) {
        pnModSetVar('mGallery2', 'g2embtheme', $g2embtheme);
    } else {
        pnModSetVar('mGallery2', 'g2embtheme', '');
    }


    // The configuration has been changed, so we clear all cache for this module
    $pnRender = pnRender::getInstance('mGallery2');
//    $pnRender->clear_cache();

    // the module configuration has been updated successfuly
    LogUtil::registerStatus(_CONFIGUPDATED);

    // Let any other modules know that the modules configuration has been updated
    pnModCallHooks('module','updateconfig', 'mGallery2', array('module' => 'mGallery2'));

    // This function generated no output
    return pnRedirect(pnModURL('mGallery2', 'admin', 'modifyconfig'));
}


/**
 * Resyncronize functionality
 *
 * @return       output       The configuration page
 */
function mGallery2_admin_resynchronize()
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('mGallery2', false);

    // Assign all the module variables to the template
    $pnRender->assign(pnModGetVar('mGallery2'));

    return $pnRender->fetch('gallery2_admin_resynchronize.htm');
}



/**
 * Update users and groups functionality
 *
 */
function mGallery2_admin_updateusersandgroups()
{
    // Security check
    if (!SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('mGallery2', 'admin', 'resynchronize'));
    }

    // The configuration has been changed, so we clear all cache for this module
    $pnRender = pnRender::getInstance('mGallery2');
    $pnRender->clear_cache();

    if (!pnModGetVar('mGallery2', 'synchronize')) {
        return LogUtil::registerError(_MG2SYNCDISABLED);
    }

    // Init G2
    $result = mGallery2_helper_init();
    if (!$result['success']) {
        return LogUtil::registerError($result['error_message']);
    }

    // get groups
    // we do this first, in order to save unnecessary loops
    // and therefore tons of sql queries ;)
    $syncgroups = pnModGetVar('mGallery2', 'syncgroups');
    if ($syncgroups == 'all') {
        $zkgroups = pnModAPIFunc('Groups', 'user', 'getall');
        foreach ($zkgroups as $zkgroup) {
            if ((pnModGetVar('mGallery2', 'skipemptygroup') == '1') &&
                (pnModAPIFunc('Groups', 'user', 'countgroupmembers', array('gid' => $zkgroup['gid'])) == '0')) {
                continue;
            }
            $ret = GalleryEmbed::isExternalIdMapped($zkgroup['gid'], 'GalleryGroup');
            if ($ret) {
               if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) {
                    $result = mGallery2_helper_create_group($zkgroup['gid']);
                    if (!$result['success']) {
                        return LogUtil::registerError($result['error_message'], 404);
                    }
                } else {
                    return $ret->getAsHtml();
                }
            } else {
                $result = mGallery2_helper_update_group($zkgroup['gid']);
                if (!$result['success']) {
                    return LogUtil::registerError($result['error_message'], 404);
                }
            }
        }
    }

//FIXME: pass user array to user_create func? nope
    // get users
    $users = array();
    $syncusers = pnModGetVar('mGallery2', 'syncusers');
    if ($syncusers == 'all') {
        $items = pnModAPIFunc('Users', 'user', 'getall');
        foreach ($items as $item) {
            $users[] = $item['uid'];
        }
    } elseif ($syncusers == 'group') {
        $zkgrs = explode(",", pnModGetVar('mGallery2', 'zkgroup'));
        foreach ($zkgrs as $zkgr) {
            $items = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $zkgr));
            foreach ($items['members'] as $item) {
                $users[] = $item['uid'];
            }
        }

        // sync admins always
        $admingroups = pnModApiFunc('mGallery2', 'admin', 'getadmingroups');
        if ($admingroups) {
            foreach ($admingroups as $admingroup) {
                if (!in_array($admingroup, $zkgrs)) {
                    $admins = pnModAPIFunc('Groups', 'user', 'get', array('gid' => '2'));
                    foreach ($admins['members'] as $admin) {
                        $users[] = $admin['uid'];
                    }
                }
            }
        }
/*
//FIXME: sync admins always?
//       what about those that have admin access to the module only?
        // admins are always synced
        if (!in_array('2', $zkgrs)) {
            $admins = pnModAPIFunc('Groups', 'user', 'get', array('gid' => '2'));
            foreach ($admins['members'] as $admin) {
                $users[] = $admin['uid'];
            }
        }
*/
        array_unique($users);
    }

    foreach ($users as $uid) {
        // except guest user
        if ($uid != 1) {
            $ret = GalleryEmbed::isExternalIdMapped($uid, 'GalleryUser');
            if ($ret) {
                if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) {
                    $result = mGallery2_helper_create_user($uid);
                    if (!$result['success']) {
                        return LogUtil::registerError($result['error_message'], 404);
                    }
                } else {
                    return $ret->getAsHtml();
                }
            } else {
                $result = mGallery2_helper_update_user($uid);
                if (!$result['success']) {
                    return LogUtil::registerError($result['error_message'], 404);
                }
            }
        }
    }

    // complete the G2 transaction
    mGallery2_helper_term();

    // the module configuration has been updated successfuly
    LogUtil::registerStatus(_MG2SYNCHRONIZED);

    return pnRedirect(pnModURL('mGallery2', 'admin', 'resynchronize'));
}
