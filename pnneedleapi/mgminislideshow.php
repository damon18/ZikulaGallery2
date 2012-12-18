<?php
/**
 * mGallery2 minislideshow needle
 *
 * The mGallery2 module integrates Menalto Gallery2 into Zikula
 *
 * @copyright   (c) Zikula Development Team
 * @author      Mateo Tibaquirá
 * @link        http://code.zikula.org/mgallery2/
 * @version     $Id: mgminislideshow.php 88 2010-02-15 23:50:08Z mateo $
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */


/**
 * gminislideshow needle
 * @param  nid      int   needle id
 * @return string   replaced value for the needle
 */
function mGallery2_needleapi_mgminislideshow($args)
{
    // Get arguments from argument array
    $nid = $args['nid'];
    unset($args);

    // cache the results
    static $cache;
    if (!isset($cache)) {
        $cache = array();
    }

    pnModLangLoad('mGallery2');
    if (!empty($nid)) {
        if (!isset($cache[$nid])) {
            // not in cache array

            if (!pnModAvailable('mGallery2')) {
                $cache[$nid] = '<em>' . DataUtil::formatForDisplay(pnML(_MODULENOTAVAILABLE, array('s' => 'mGallery2'))) . '</em>';
            }

            $result = '<em title="' . DataUtil::formatForDisplay(sprintf(_MH_NEEDLEDATAERROR, $nid, 'mGallery2')) . '">GMINISLIDESHOW' . $nid . '</em>';

            // set the param names and their defaults
            $parameters = array(
                'g2_itemId' => 0,
                'delay' => 3,
                'showTitle' => 'bottom',
                'g2_maxImageWidth' => '600',
                'g2_maxImageHeight' => '600',
                'roundedMask' => 'true',
                'shuffle' => 'true',
                'showDropShadow' => 'true',
                'transInType' => 'Fade',
                'transOutType' => 'Random',
                'useFull' => 'true'
            );

            // explode the required parameters
            $paramk = array_keys($parameters);
            $params = explode('-', $nid);

            // loop the parameters
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 1: // g2_itemId
                    case 2: // delay
                    case 4: // g2_maxImageWidth
                    case 5: // g2_maxImageHeight
                        $parameters[$paramk[$key-1]] = (int)$value;
                        break;
                    case 3: // showTitle
                    case 9: // transInType
                    case 10: // transOutType
                        $parameters[$paramk[$key-1]] = $value; // TODO validate
                        break;
                    case 6: // roundedMask
                    case 7: // shuffle
                    case 8: // showDropShadow
                    case 11: // useFull
                        if (in_array($value, array('true', 'false'))) {
                            $parameters[$paramk[$key-1]] = $value;
                        }
                        break;
                }
            }

            // build the result
            $baseurl = pnGetBaseURL();
            if (StringUtil::right($baseurl, 1) == '/') {
                $baseurl = StringUtil::left($baseurl, strlen($baseurl) - 1);
            }
            $galleryURL = $baseurl.pnModGetVar('mGallery2', 'g2uri');
            $cache[$nid] = '<embed pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"
                                   name="minislide" wmode="transparent" quality="high"
                                   flashvars="xmlUrl='.$galleryURL.'/mediaRss.php?g2_itemId='.$parameters['g2_itemId'].'&amp;shuffle='.$parameters['shuffle'].'&amp;showDropShadow='.$parameters['showDropShadow'].'&amp;delay='.$parameters['delay'].'&amp;transInType='.$parameters['transInType'].'&amp;transOutType='.$parameters['transOutType'].'&amp;showTitle='.$parameters['showTitle'].'&amp;roundedMask='.$parameters['roundedMask'].'&amp;g2_maxImageWidth='.$parameters['g2_maxImageWidth'].'&amp;g2_maxImageHeight='.$parameters['g2_maxImageHeight'].'&amp;useFull='.$parameters['useFull'].'"
                                   src="'.$galleryURL.'/minislideshow.swf" align="middle"
                                   width="'.$parameters['g2_maxImageWidth'].'"
                                   height="'.$parameters['g2_maxImageHeight'].'" />';
        }
        $result = $cache[$nid];

    } else {
        $result = '<em>' . DataUtil::formatForDisplay('No correct needle id given') . '</em>';
    }

    return $result;
}
