<?php
/**
 * Handler for tag autocomplete widget
 *
 * @package net.nemein.tag
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

$response = new midcom_response_xml;
$response->status = 0;

// Make sure we have search term
if (!isset($_REQUEST['search']))
{
    $response->errstr = "Search term not defined";
    $response->send();
}
$search = str_replace('*', '%', $_REQUEST['search']);

$qb = net_nemein_tag_tag_dba::new_query_builder();
$qb->add_constraint('tag', 'like', $search);
$qb->add_order('tag', 'ASC');

$results = $qb->execute();
if ($results === false)
{
    $response->errstr = "Error when executing QB";
    $response->send();
}

$response->status = 1;
$response->errstr = '';

$items = array('tag');
echo "    <results>\n";
foreach ($results as $object)
{
    $items['tag'][] = $object->tag;
}

$response->results = $items;
$response->send();
?>