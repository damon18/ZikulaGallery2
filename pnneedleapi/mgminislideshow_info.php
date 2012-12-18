<?php
/**
 * mGallery2 minislideshow needle
 *
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright   (c) Zikula Development Team
 * @author      Mateo Tibaquirá
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: mgminislideshow_info.php 88 2010-02-15 23:50:08Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */


/**
 * gminislideshow needle info
 * @param none
 * @return string with short usage description
 */
function mGallery2_needleapi_mgminislideshow_info($args)
{
    $info = array('module'  => 'mGallery2',
                  'info'    => 'MGMINISLIDESHOW{-g2_itemId-delay-showTitle-g2_maxImageWidth-g2_maxImageHeight-roundedMask-shuffle-showDropShadow-transInType-transOutType-useFull}',
                  'inspect' => false);
    return $info;
}
