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
 * @version     $Id: pnadminapi.php 87 2009-10-29 15:59:05Z ph $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    Administration model - all user database and process functions
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(dirname(__FILE__).'/g2helper.php');
 
/**
 * create a new mGallery2 item
 * 
 * @param    $args['itemname']  name of the item
 * @param    $args['number']    number of the item
 * @return   int                Example item ID on success, false on failure
 */
function mGallery2_adminapi_createhook($args)
{

    // Argument check
    if (!isset($args['objectid'])) {
        return LogUtil::registerError(_MODARGSERROR);
    }

    if (pnModGetVar('mGallery2', 'synchronize')) {
        $result = mGallery2_helper_init();
        if (!$result['success'] && !($result['error_code'] & ERROR_MISSING_OBJECT)) {
            return LogUtil::registerError($result['error_message']);
        }

        if ($args['extrainfo']['module'] == 'Users') {
            $uid = $args['objectid'];

            $syncusers = pnModGetVar('mGallery2', 'syncusers');
            if ($syncusers == 'group') {
                $zkgroups = explode(",", pnModGetVar('mGallery2', 'zkgroup'));
                $defaultgroup = pnModGetVar('Groups', 'defaultgroup');
                if (!in_array($defaultgroup, $zkgroups)) {
                    return true;
                }
            }

            $result = mGallery2_helper_create_user($uid);
            if (!$result['success']) {
                return LogUtil::registerError($result['error_message']);
            }
        } elseif ($args['extrainfo']['module'] == 'Groups') {
            $gid = $args['objectid'];

            if ((pnModGetVar('mGallery2', 'syncgroups') == 'all') &&
                (pnModGetVar('mGallery2', 'skipemptygroup') == 0)) {
//FIXME: set session var, return and wait for update hook
//      instead of session var, we can also check if group exists in mGallery2_helper_create_group()
//      but maybe session var is better since this is a known bug in zk
// also, the above checks are unnecessary
// BETTER: check for empty values

                // work around http://code.zikula.org/core/ticket/669
                // return if name is empty and wait for update hook
                // could also fake the name, avoiding to check if group exists
                //  or set session var
                $gdata = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $gid));
                if ($gdata && ($gdata['name'] == '')) {
                    return true;
                }

                $result = mGallery2_helper_create_group($gid);
                if (!$result['success']) {
                    return LogUtil::registerError($result['error_message']);
                }
            }
        }

        mGallery2_helper_term();
    }

    // Let the calling process know that we have finished successfully
    return true;    
}

/**
 * delete an item
 * 
 * @param    $args['tid']   ID of the item
 * @return   bool           true on success, false on failure
 */
function mGallery2_adminapi_deletehook($args)
{

    // Argument check
    if (!isset($args['objectid'])) {
        return LogUtil::registerError(_MODARGSERROR);
    }

    if (pnModGetVar('mGallery2', 'synchronize')) {
        $result = mGallery2_helper_init();
        if (!$result['success'] && !($result['error_code'] & ERROR_MISSING_OBJECT)) {
            return LogUtil::registerError($result['error_message']);
        }

        if ($args['extrainfo']['module'] == 'Users') {
            $uid = $args['objectid'];

            $result = mGallery2_helper_delete_user($uid);
            if (!$result['success']) {
                return LogUtil::registerError($result['error_message']);
            }
        } elseif ($args['extrainfo']['module'] == 'Groups') {
            $gid = $args['objectid'];

            $result = mGallery2_helper_delete_group($gid);
            if (!$result['success']) {
                return LogUtil::registerError($result['error_message']);
            }
        }

        mGallery2_helper_term();
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * update an item
 * 
 * @param    $args['tid']     the ID of the item
 * @param    $args['itemname']    the new name of the item
 * @param    $args['number']  the new number of the item
 * @return   bool             true on success, false on failure
 */
function mGallery2_adminapi_updatehook($args)
{

    // Argument check
    if (!isset($args['objectid'])) {
        return LogUtil::registerError(_MODARGSERROR);
    }

    if (pnModGetVar('mGallery2', 'synchronize')) {
        $result = mGallery2_helper_init();
        if (!$result['success'] && !($result['error_code'] & ERROR_MISSING_OBJECT)) {
            return LogUtil::registerError($result['error_message']);
        }

        if ($args['extrainfo']['module'] == 'Users') {
            $uid = $args['objectid'];
//FIXME: check if user is mapped or allowed

            $result = mGallery2_helper_update_user($uid);
            if (!$result['success']) {
                return LogUtil::registerError($result['error_message']);
            }
        } elseif ($args['extrainfo']['module'] == 'Groups') {
            $gid = $args['objectid'];
            if ($args['extrainfo']['action'] == 'adduser') {
                $uid = $args['extrainfo']['uid'];

                $result = mGallery2_helper_adduser_group($gid, $uid);
                if (!$result['success']) {
                    return LogUtil::registerError($result['error_message']);
                }
            } elseif ($args['extrainfo']['action'] == 'removeuser') {
                $uid = $args['extrainfo']['uid'];

                $result = mGallery2_helper_removeuser_group($gid, $uid);
                if (!$result['success']) {
                    return LogUtil::registerError($result['error_message']);
                }
            } else {
                $result = mGallery2_helper_update_group($gid);
                if (!$result['success']) {
                    return LogUtil::registerError($result['error_message']);
                }
            }
        }

        mGallery2_helper_term();
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * Get admin links
 * 
 * @return   array      array of admin links
 */
function mGallery2_adminapi_getlinks()
{
    $links = array ();
    if (SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN)) {
        $links[] = array (
            'url'  => pnModURL('mGallery2', 'admin', 'resynchronize'),
            'text' => _MG2RESYNCHRONIZE
        );
        $links[] = array (
            'url'  => pnModURL('mGallery2', 'admin', 'modifyconfig'),
            'text' => _MODIFYCONFIG
        );
    }
    return $links;
}

function mGallery2_adminapi_getadmingroups()
{
    $pntable = pnDBGetTables();
    $grouppermcolumn = $pntable['group_perms_column'];
    $where = "WHERE ($grouppermcolumn[component]='mGallery2::' OR $grouppermcolumn[component]='.*') AND $grouppermcolumn[instance]='.*' AND $grouppermcolumn[level]='800'";
    $items = DBUtil::selectObjectArray('group_perms', $where);
    if ($items) {
        $groups = array();
        foreach ($items as $item) {
            $groups[] = $item['gid'];
        }
        return $groups;
    }
    return false;
}

