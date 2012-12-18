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
 * @version     $Id: pninit.php 86 2009-10-29 15:39:14Z ph $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    Installation, upgrade and uninstall functions
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */


/**
 * initialise the mGallery2 module
 *
 * @return       bool       true on success, false otherwise
 */
function mGallery2_init()
{
    // Set up the module vars
    pnModSetVar('mGallery2', 'basedirectory', pnServerGetVar('DOCUMENT_ROOT').'gallery2');
    pnModSetVar('mGallery2', 'g2uri', '/gallery2');
    pnModSetVar('mGallery2', 'synchronize', 0);
    pnModSetVar('mGallery2', 'sideblock', 0);
    
    // Variables for the transform hook
    pnModSetVar('mGallery2', 'fullSize', 0); // show image full size?
    pnModSetVar('mGallery2', 'maxSize', 0); // (closest) maximum size for fullsize images
    pnModSetVar('mGallery2', 'showTitle', 0); // show title with image?
    pnModSetVar('mGallery2', 'showViews', 0); // show the number of views?
    pnModSetVar('mGallery2', 'showDate', 0); // show image date?
    pnModSetVar('mGallery2', 'showOwner', 0); // show the image owner?
    pnModSetVar('mGallery2', 'albumFrame', 'none'); // what frame to use with albums
    pnModSetVar('mGallery2', 'itemFrame', 'none'); // what frame to use with images

    if (!pnModRegisterHook('item', 'create', 'API', 'mGallery2', 'admin', 'createhook')) {
        return Logutil::registerError(_REGISTERFAILED.' (createhook)');
    }
    if (!pnModRegisterHook('item', 'delete', 'API', 'mGallery2', 'admin', 'deletehook')) {
        return Logutil::registerError(_REGISTERFAILED.' (deletehook)');
    }
    if (!pnModRegisterHook('item', 'update', 'API', 'mGallery2', 'admin', 'updatehook')) {
        return Logutil::registerError(_REGISTERFAILED.' (updatehook)');
    }

    // transform hook
    if (!pnModRegisterHook('item', 'transform', 'API', 'mGallery2', 'user', 'transform')) {
        return Logutil::registerError(_REGISTERFAILED.' (transform hook)');
    }

    // Initialisation successful
    return true;
}


/**
 * upgrade the mGallery2 module from an old version
 *
 * @return       bool       true on success, false otherwise
 */
function mGallery2_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch($oldversion) {
        case 0.0:
            // Code to upgrade from version 0.0 goes here
            break;
        case 0.1:
            // Add transform hook
            if (!pnModRegisterHook('item', 'transform', 'API', 'mGallery2', 'user', 'transform')) {
                return Logutil::registerError(_REGISTERFAILED.' (transform hook)');
            }

            // set variables for the transform hook
            pnModSetVar('mGallery2', 'fullSize', 0); // show image full size?
            pnModSetVar('mGallery2', 'maxSize', 0); // (closest) maximum size for fullsize images
            pnModSetVar('mGallery2', 'showTitle', 0); // show title with image?
            pnModSetVar('mGallery2', 'showDate', 0); // show image date?
            pnModSetVar('mGallery2', 'showOwner', 0); // show the image owner?
            pnModSetVar('mGallery2', 'albumFrame', 'none'); // what frame to use with albums
            pnModSetVar('mGallery2', 'itemFrame', 'none'); // what frame to use with images
            break;
    }

    // Update successful
    return true;
}


/**
 * delete the mGallery2 module
 *
 * @return       bool       true on success, false otherwise
 */
function mGallery2_delete()
{
    if (!pnModUnregisterHook('item', 'create', 'API', 'mGallery2', 'admin', 'createhook')) {
        return Logutil::registerError(_UNREGISTERFAILED.' (createhook)');
    }
    if (!pnModUnregisterHook('item', 'delete', 'API', 'mGallery2', 'admin', 'deletehook')) {
        return Logutil::registerError(_UNREGISTERFAILED.' (deletehook)');
    }
    if (!pnModUnregisterHook('item', 'update', 'API', 'mGallery2', 'admin', 'updatehook')) {
        return Logutil::registerError(_UNREGISTERFAILED.' (updatehook)');
    }
    // transform hook
    if (!pnModUnregisterHook('item', 'transform', 'API', 'mGallery2', 'user', 'transform')) {
        return Logutil::registerError(_UNREGISTERFAILED.' (transform hook)');
    }

    // Delete any module variables
    pnModDelVar('mGallery2');

    // Deletion successful
    return true;
}
