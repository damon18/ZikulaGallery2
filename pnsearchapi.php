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
 * @version     $Id$
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @abstract    G2 bridge
 * @package     Zikula_3rd_party_Modules
 * @subpackage  mgallery2
 */

Loader::requireOnce(dirname(__FILE__).'/g2helper.php');

/**
 * Search plugin info
 **/
function mGallery2_searchapi_info()
{
    return array('title' => 'mGallery2', 
                 'functions' => array('mGallery2' => 'search'));
}

/**
 * Search form component
 **/
function mGallery2_searchapi_options($args)
{
    if (SecurityUtil::checkPermission( 'mGallery2::', '::', ACCESS_READ)) { $pnRender = pnRender::getInstance('mGallery2');
        $pnRender->assign('active',(isset($args['active'])&&isset($args['active']['mGallery2']))||(!isset($args['active'])));
        return $pnRender->fetch('gallery2_search_options.htm');
    }

    return '';
}

/**
 * Search plugin main function
 **/
function mGallery2_searchapi_search($args)
{
    pnModDBInfoLoad('Search');
    $pntable = pnDBGetTables();
    $searchTable = $pntable['search_result'];
    $searchColumn = $pntable['search_result_column'];

//print_r($args);

    $sessionId = session_id();

    $result = mGallery2_helper_init();
    if ($result['success']) {
        list ($ret, $results) = GalleryEmbed::searchScan($args['q'], $args['numlimit']);
        if (!$ret) {
//print_r($results);
            foreach ($results as $name => $module) {
                if (count($module['results']) > 0) {
                    foreach ($module['results'] as $result) {
                        $objArray[] = array('title' => $result['fields'][0]['value'],
                                            'text' => $result['fields'][1]['value'],
                                            'itemid' => $result['itemId'],
                                            'cr_date' => '',
                                            'type'  => $module['name']);
                    }
                }
            }
        }
    }

    $insertSql = 
"INSERT INTO $searchTable
  ($searchColumn[title],
   $searchColumn[text],
   $searchColumn[extra],
   $searchColumn[created],
   $searchColumn[module],
   $searchColumn[session])
VALUES ";

    // Process the result set and insert into search result table
    foreach ($objArray as $obj) {

        $extra = serialize(array('g2id' => $obj['itemid']));


        $sql = $insertSql . '(' 
                   . '\'' . DataUtil::formatForStore($obj['title']) . '\', '
                   . '\'' . DataUtil::formatForStore($obj['text']) . '\', '
                   . '\'' . DataUtil::formatForStore($extra) . '\', '
                   . '\'' . DataUtil::formatForStore($obj['cr_date']) . '\', '
                   . '\'' . 'mGallery2' . '\', '
                   . '\'' . DataUtil::formatForStore($sessionId) . '\')';
        $insertResult = DBUtil::executeSQL($sql);
        if (!$insertResult) {
            return LogUtil::registerError (_GETFAILED);
        }
    }

    return true;
}

/**
 * Do last minute access checking and assign URL to items
 *
 * Access checking is ignored since access check has
 * already been done. But we do add a URL to the found item
 */
function mGallery2_searchapi_search_check(&$args)
{

    $datarow = &$args['datarow'];
    $extra = unserialize($datarow['extra']);

    $result = mGallery2_helper_init();
    if ($result['success']) {
        global $gallery;
        $urlGenerator =& $gallery->getUrlGenerator();
        $datarow['url'] = $urlGenerator->generateUrl(array('itemId'       => $extra['g2id']),
                                                     array('baseUrl'      => pnModUrl('mGallery2', 'user', 'main'),
                                                           'htmlEntities' => false));
    }
    return true;
}
