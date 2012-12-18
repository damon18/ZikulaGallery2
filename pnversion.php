<?php
/**
 * mGallery2 module
 * 
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright (c) 2002-2008, Zikula Development Team
 * @author      Michael Bhola
 * @link        http://code.zikula.org/mgallery2/
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    Provide version and credit information about the module
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

// Module info
$modversion['name']           = 'mGallery2';
$modversion['version']        = '0.2';
$modversion['description']    = _MG2_DESCRIPTION;
$modversion['displayname']    = _MG2_DISPLAYNAME;

// Used by the Credits module
$modversion['credits']        = 'pndocs/credits.txt';
$modversion['changelog']      = 'pndocs/changelog.txt';
$modversion['help']           = 'pndocs/help.txt';
$modversion['license']        = 'pndocs/license.txt';
$modversion['official']       = 0;
$modversion['author']         = 'Zikula development team';
$modversion['contact']        = 'http://code.zikula.org/mgallery2';

// Info for Zikula core
$modversion['admin']          = 1;

// Module security
$modversion['securityschema'] = array('mGallery2::' => 'Item name::Item ID');
