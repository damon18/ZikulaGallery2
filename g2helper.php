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
 * @version     $Id: g2helper.php 85 2009-08-20 23:24:04Z ph $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    G2 bridge
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

function mGallery2_helper_init()
{
    static $initialised;

    if (!isset($initialised)) {
        $initialised = false;
    }

    if (!$initialised) {
        if(!file_exists(pnModGetVar('mGallery2', 'basedirectory').'/embed.php')) {
            return array('success' => false);
//          require_once('embed.php');
            Loader::requireOnce('embed.php');
        } else {
//          require_once(pnModGetVar('mGallery2', 'basedirectory').'/embed.php');
            Loader::requireOnce(pnModGetVar('mGallery2', 'basedirectory').'/embed.php');
        }

        if (pnUserLoggedIn()) {
            $uid = pnUserGetVar('uid');
        } else {
            // if anonymous user, set g2 activeUser to ''
            $uid = '';
        }

        // convert language code
        $zk2g2lang = array('eng' => 'en',
                           'deu' => 'de',
                           'dan' => 'da',
                           'fra' => 'fr',
                           'ita' => 'it',
                           'nld' => 'nl',
                           'nor' => 'no',
                           'pol' => 'pl',
                           'spa' => 'es',
                           'swe' => 'sv',
                           'tur' => 'tr');

        // initiate G2 
        $ret = GalleryEmbed::init(array('fullInit'       => (pnModGetName() == 'mGallery2' ? false : true),
                                        'g2Uri'          => pnModGetVar('mGallery2', 'g2uri').'/',
                                        'embedUri'       => pnModURL('mGallery2', 'user', 'main', array(), null, null, true),
                                        'activeUserId'   => $uid,
                                        'activeLanguage' => $zk2g2lang[pnUserGetLang()],
                                        'loginRedirect'  => pnModURL('Users', 'user', 'loginscreen')));

        if ($ret && ($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
            if (pnModGetVar('mGallery2', 'synchronize')) {
                $syncusers = pnModGetVar('mGallery2', 'syncusers');
                if ($syncusers == 'group') {
                    $zkgroups = explode(",", pnModGetVar('mGallery2', 'zkgroup'));
                    $usergroups = pnModAPIFunc('Groups', 'user', 'getusergroups', array('uid' => $uid));
                    foreach ($usergroups as $usergroup) {
                        if (in_array($usergroup['gid'], $zkgroups)) {
                            $found = 1;
                        }
                    }
                }
                if (isset($found) || ($syncusers == 'all')) {
                    $result = mGallery2_helper_create_user($uid);
                    if (!$result['success']) {
                        return $result;
                    }
//FIXME: call init again now!?
                }
            }
        } //FIXME: else... set uid to '' and re-init for guest view?

        $initialised = true;

//        GalleryEmbed::done();
        register_shutdown_function(mGallery2_helper_term);
    }

    return array('success' => true);
}

function mGallery2_helper_handle_request()
{
    static $handled;

    if (!isset($handled)) {
        $handled = false;
    }

    if (!$handled) {
    /* At this point we know that either the user either existed already before or that it was just created
     * proceed with the normal request to G2 */

        // user interface: you could disable sidebar in G2 and get it as separate HTML to put it into a block

//      GalleryCapabilities::set('showSidebarBlocks', pnModGetVar('mGallery2', 'sideblock') ? true : false);
        GalleryCapabilities::set('showSidebarBlocks', pnModGetVar('mGallery2', 'sideblock') ? false : true);

        // set theme for embedded mode
        // If the specified theme is unavailable, incompatible or inactive, it is ignored.
        $embtheme = pnModGetVar('mGallery2', 'g2embtheme');
        if ($embtheme) {
            GalleryEmbed::setThemeForRequest($embtheme);
        }

        global $g2moddata;

        // handle the G2 request
        $g2moddata = GalleryEmbed::handleRequest();

        $handled = true;

        // show error message if isDone is not defined
        if (!isset($g2moddata['isDone'])) {
            /* put this into lang or select an error message !!*/
            return array('success'       => false,
                         'error_message' => _MG2FATALERROR);
        }

        // exit if it was an immediate view / request (G2 already outputted some data)
        if ($g2moddata['isDone']) {
            exit; 
        }
    }

    return array('success' => true);
}

function mGallery2_helper_term()
{
    GalleryEmbed::done();
}

function mGallery2_helper_create_user($uid)
{
    if (!isset($uid)) {
        return LogUtil::registerError(_MODARGSERROR);
    }

//FIXME: check zkgroup (also passed from pnadmin) ?
    if (pnModGetVar('mGallery2', 'synchronize')) {

        // get and assign user vars
        $uvars = pnUserGetVars($uid);
        if (!isset($uvars['name']) || empty($uvars['name'])) {
            $uvars['name'] = $uvars['uname'];
        }
        if (pnModGetVar('mGallery2', 'supportsha', false)) {
            $hasharray  = pnModAPIFunc('Users', 'user', 'gethashmethods', array('reverse' => true));
            $hashmethod = $hasharray[$uvars['hash_method']];
        } else {
            $hashmethod = 'md5';
        }

        $ret = GalleryEmbed::isExternalIdMapped($uid, 'GalleryUser');
        if ($ret) {
            if (!($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        } else {
            $ret = GalleryCoreApi::removeMapEntry('ExternalIdMap',
                                                  array('externalId' => $uid,
                                                        'entityType' => 'GalleryUser'));
            if ($ret) {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }

        $ret = GalleryEmbed::createUser($uid, array('username'       => $uvars['uname'],
                                                    'fullname'       => $uvars['name'],
                                                    'email'          => $uvars['email'],
                                                    'hashedpassword' => $uvars['pass'],
                                                    'hashmethod'     => $hashmethod));

        list($re, $user) = GalleryCoreApi::fetchUserByUsername($uvars['uname']);
        if ($re) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }

        $g2uid = $user->getId();
        if ($ret) {
            if ($ret->getErrorCode() & ERROR_COLLISION) {
                $ret = GalleryEmbed::addExternalIdMapEntry($uid, $g2uid, 'GalleryUser');
                if ($ret) {
                    return array('success'       => false,
                                 'error_code'    => $ret->getErrorCode(),
                                 'error_message' => $ret->getAsHtml());
                }
            } else {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }

        // handle g2 group memberships
        $usergroups = pnModAPIFunc('Groups', 'user', 'getusergroups', array('uid' => $uid));
/*        // admin users into admin group
        foreach ($usergroups as $usergroup) {
            if ($usergroup['gid'] == 2) {
                $ret = GalleryCoreApi::addUserToGroup($g2uid, 3);
                if ($ret) {
                    return array('success'       => false,
                                 'error_code'    => $ret->getErrorCode(),
                                 'error_message' => $ret->getAsHtml());
                }
                break;
            }
        }
*/
        $syncgroups = pnModGetVar('mGallery2', 'syncgroups');

        // admins are always synced and pot into admin group
        if (SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN, $uid)) {
            $ret = GalleryCoreApi::addUserToGroup($g2uid, 3);
            if ($ret) {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }

        if ($syncgroups == 'all') {
            foreach ($usergroups as $usergroup) {
                $result = mGallery2_helper_adduser_group($usergroup['gid'], $uid);
                if (!$result['success']) {
                    // we may need to create the group first
                    // even though it should have been created before!
                    $result = mGallery2_helper_create_group($usergroup['gid']);
                    if (!$result['success']) {
                        return LogUtil::registerError($result['error_message'], 404);
                    }
                    // and then add the user
                    $result = mGallery2_helper_adduser_group($usergroup['gid'], $uid);
                    if (!$result['success']) {
                        return LogUtil::registerError($result['error_message'], 404);
                    }
                }
            }
        } elseif ($syncgroups == 'group') {
            $g2groups = explode(",", pnModGetVar('mGallery2', 'g2group'));
            $g2group_opts = pnModGetVar('mGallery2', 'g2group_opts');
            $g2idmap = mGallery2_helper_getg2group_idmap();
            foreach ($g2groups as $g2group) {
                // exclude 'Registered Users' and 'Everybody' groups
                if ($g2group == 2 || $g2group == 4) {
                    continue;
                }
                //opt1: put the user in all of the defined groups
                //opt2: put him in those groups only, that he is a member of
                if ($g2group_opts == 'opt2') {
                    // because of this weird array matrix, we will have to use loop
                    // see http://code.zikula.org/core/ticket/702
                    $gidfound = '0';
                    foreach ($usergroups as $ugroup) {
//                        if ($g2idmap[$g2group]['externalId'] == $usergroup['gid']) {
                        if (in_array($g2group, $g2idmap) &&
                            array_key_exists($usergroup['gid'], $g2idmap)) {
                            $gidfound = '1';
                        }
                    }
                    if ($gidfound != '1') {
                        continue;
                    }
                }

                $ret = GalleryCoreApi::addUserToGroup($g2uid, $g2group);
                if ($ret) {
                    return array('success'       => false,
                                 'error_code'    => $ret->getErrorCode(),
                                 'error_message' => $ret->getAsHtml());
                }
            }
        }
    }

    // Let the calling process know that we have finished successfully
//    return mGallery2_helper_update_user($uid);    
    return array('success' => true);
}

function mGallery2_helper_delete_user($uid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
        $ret = GalleryEmbed::deleteUser($uid);
        if ($ret && !($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }
    }

    // Let the calling process know that we have finished successfully
    return array('success' => true);
}

function mGallery2_helper_update_user($uid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
// if the user is allowed to be synchronized is being checked in those functions calling this
// therefore we might even get rid of the if-clause above...
        // get and assign user vars
        $uvars = pnUserGetVars($uid);
        if (!isset($uvars['name']) ||
            empty($uvars['name']) ||
            !$uvars['name']) {
            $uvars['name'] = $uvars['uname'];
        }
        if (pnModGetVar('mGallery2', 'supportsha', false)) {
            $hasharray  = pnModAPIFunc('Users', 'user', 'gethashmethods', array('reverse' => true));
            $hashmethod = $hasharray[$uvars['hash_method']];
        } else {
            $hashmethod = 'md5';
        }

        $ret = GalleryEmbed::updateUser($uid, array('username'       => $uvars['uname'],
                                                    'fullname'       => $uvars['name'],
                                                    'email'          => $uvars['email'],
                                                    'hashedpassword' => $uvars['pass'],
                                                    'hashmethod'     => $hashmethod));
        if ($ret) {
            if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) {
                return mGallery2_helper_create_user($uid);
            } else {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }

        // handle group stuff here
        // at this point all groups are already synced,
        // so we are just comparing zkgroups to g2groups membership
        // and correct it accordingly...
// all g2groups available: list ($re, $gallery2groups) = GalleryCoreApi::fetchGroupNames();
// all g2groups uid is currently memeber of: gallerycoreapi::fetchGroupsForUser
// all zkgroups uid is memeber of: $usergroups = pnModAPIFunc('Groups', 'user', 'getusergroups', array('uid' => $uid));
      
//FIXME: could also use
// GalleryCoreApi::loadEntityByExternalId($uid, 'GalleryUser');
        list($re, $g2user) = GalleryCoreApi::fetchUserByUsername($uvars['uname']);
        if ($re) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }

        $g2uid = $g2user->getId();
        $zk_usergroups = pnModAPIFunc('Groups', 'user', 'getusergroups', array('uid' => $uid));
        list ($ret, $g2_usergroups) = GalleryCoreApi::fetchGroupsForUser($g2uid); // check $ret
        if ($ret) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }
        $g2extidmap = mGallery2_helper_getg2group_idmap();
        // retrieve externalid for each group
/*        $g2grarray = array();
        foreach ($g2_usergroups['1'] as $g2grid => $g2grname) {
            $g2gridmap = GalleryEmbed::getExternalIdMap($g2grid);
            $g2grarray[$g2grid] = $g2gridmap[$g2gridmap]['externalId'];
        }
*/        $syncgroups = pnModGetVar('mGallery2', 'syncgroups');

        // admins are always synced and put into admin group
        if (SecurityUtil::checkPermission('mGallery2::', '::', ACCESS_ADMIN, $uid)) {
            $ret = GalleryCoreApi::addUserToGroup($g2uid, 3);
            if ($ret) {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }

        if ($syncgroups == 'all') {
            foreach ($zk_usergroups as $zk_usergroup) {
                if ($g2extidmap && $g2extidmap[$zk_usergroup['gid']]) {
                    if (!array_key_exists($g2extidmap[$zk_usergroup['gid']], $g2_usergroups)) {
                        // add user to g2 group
                        $ret = GalleryEmbed::addUserToGroup($uid, $zk_usergroup['gid']);
//FIXME: check if group exists and is mapped, but it should be checked in previous if-clause
                        if ($ret) {
                            return array('success'       => false,
                                         'error_code'    => $ret->getErrorCode(),
                                         'error_message' => $ret->getAsHtml());
                        }
                    }
                }
            }
        } elseif ($syncgroups == 'group') {
            $putingroups = explode(",", pnModGetVar('mGallery2', 'g2group'));
            $g2group_opts = pnModGetVar('mGallery2', 'g2group_opts');
            if ($g2group_opts == 'opt2') {
                foreach ($zk_usergroups as $zk_usergroup) {
                    if ($g2extidmap && $g2extidmap[$zk_usergroup['gid']]) {
                        if (in_array($g2extidmap[$zk_usergroup['gid']], $putingroups)) {
                            // add user to g2 group
                            $ret = GalleryEmbed::addUserToGroup($uid, $zk_usergroup['gid']);
//FIXME: check if group exists and is mapped, but it should be checked in previous if-clause
                            if ($ret) {
                                return array('success'       => false,
                                             'error_code'    => $ret->getErrorCode(),
                                             'error_message' => $ret->getAsHtml());
                            }
                        }
                    } //FIXME: check that groups are really synced already, else we would have to create it
                      //        though this should be a problem only for certain circumstances
                }
            } elseif ($g2group_opts == 'opt1') {
                foreach ($putingroups as $putingroup) {
                    $ret = GalleryCoreApi::addUserToGroup($g2uid, $putingroup);
                    if ($ret) {
                        return array('success'       => false,
                                     'error_code'    => $ret->getErrorCode(),
                                     'error_message' => $ret->getAsHtml());
                    }
                }
            }
        }
    }

    // Let the calling process know that we have finished successfully
    return array('success' => true);
}

function mGallery2_helper_create_group($gid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
//        $name  = pnModAPIFunc('Groups', 'admin', 'getnamebygid', array('gid' => $gid));
        $gdata = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $gid));
        $name  = $gdata['name'];
        $ret   = GalleryEmbed::isExternalIdMapped($gid, 'GalleryGroup');
        if ($ret) {
            if (!($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        } else {
            $ret = GalleryCoreApi::removeMapEntry('ExternalIdMap',
                                                  array('externalId' => $gid,
                                                        'entityType' => 'GalleryGroup'));
            if ($ret) {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }

        $ret = GalleryEmbed::createGroup($gid, $name);
        if ($ret) {
            if ($ret->getErrorCode() & ERROR_COLLISION) {
                list($ret, $group) = GalleryCoreApi::fetchGroupByGroupName($name);
                if ($ret) {
                    return array('success'       => false,
                                 'error_code'    => $ret->getErrorCode(),
                                 'error_message' => $ret->getAsHtml());
                }
                $ret = GalleryEmbed::addExternalIdMapEntry($gid, $group->getId(), 'GalleryGroup');
                if ($ret) {
                    return array('success'       => false,
                                 'error_code'    => $ret->getErrorCode(),
                                 'error_message' => $ret->getAsHtml());
                }
            } else {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }
    }

    // Let the calling process know that we have finished successfully
    // return mGallery2_helper_update_group($gid);
    return array('success' => true);
}

function mGallery2_helper_delete_group($gid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
        $ret = GalleryEmbed::deleteGroup($gid);
        if ($ret && !($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }
    }

    // Let the calling process know that we have finished successfully
    return array('success' => true);
}

function mGallery2_helper_update_group($gid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
//        $name  = pnModAPIFunc('Groups', 'admin', 'getnamebygid', array('gid' => $gid));
        $gdata = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $gid));

        $ret = GalleryEmbed::updateGroup($gid, array('groupname' => $gdata['name']));
        if ($ret) {
            if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) {
                return mGallery2_helper_create_group($gid);
            } else {
                return array('success'       => false,
                             'error_code'    => $ret->getErrorCode(),
                             'error_message' => $ret->getAsHtml());
            }
        }
    }

    // Let the calling process know that we have finished successfully
    return array('success' => true);
}

function mGallery2_helper_adduser_group($gid, $uid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
        $ret = GalleryEmbed::addUserToGroup($uid, $gid);    
        if ($ret && !($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }
    }

    // Let the calling process know that we have finished successfully
    return array('success' => true);
}

function mGallery2_helper_removeuser_group($gid, $uid)
{
    if (pnModGetVar('mGallery2', 'synchronize')) {
        $ret = GalleryEmbed::removeUserFromGroup($uid, $gid);    
        if ($ret && !($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
            return array('success'       => false,
                         'error_code'    => $ret->getErrorCode(),
                         'error_message' => $ret->getAsHtml());
        }
    }

    // Let the calling process know that we have finished successfully
    return array('success' => true);
}

function mGallery2_helper_convert_encoding($string)
{
    if (function_exists(mb_convert_encoding)) {
        return mb_convert_encoding($string, _CHARSET, 'UTF-8');
    } else {
        return $string;
    }
}

function mGallery2_helper_getg2group_idmap()
{
    list ($ret, $results) = GalleryCoreApi::getMapEntry('ExternalIdMap', array('entityId', 'externalId', 'entityType'));
    if ($ret) {
        return array('success'       => false,
                     'error_code'    => $ret->getErrorCode(),
                     'error_message' => $ret->getAsHtml());
    }

    $map = array();
    while ($result = $results->nextResult()) {
        if ($result[2] == 'GalleryGroup') {
            $entry = array('externalId' => $result[1],
                           'entityId' => $result[0],
                           'entityType' => $result[2]);

//            $map[$result[1]] = $entry;
            $map[$result[1]] = $result[0];
        }
    }
    return($map);
}
