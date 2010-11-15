<?php
/**
 * Handler for tag autocomplete widget
 *
 * @package net.nemein.tag
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: xml_search.php 3870 2006-08-24 16:17:29Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// Common variables
$encoding = 'UTF-8';

// Common headers
$_MIDCOM->cache->content->content_type('text/xml');
$_MIDCOM->header('Content-type: text/xml; charset=' . $encoding);
echo '<?xml version="1.0" encoding="' . $encoding . '" standalone="yes"?>' . "\n";
echo "<response>\n";

// Make sure we have search term
if (!isset($_REQUEST['search']))
{
    echo "    <status>0</status>\n";
    echo "    <errstr>Search term not defined</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}
$search = str_replace('*', '%', $_REQUEST['search']);

$qb = net_nemein_tag_tag_dba::new_query_builder();
$qb->add_constraint('tag', 'like', $search);
$qb->add_order('tag', 'ASC');

$results = $qb->execute();
if ($results === false)
{
    echo "    <status>0</status>\n";
    echo "    <errstr>Error when executing QB</errstr>\n";
    echo "</response>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}

echo "    <status>1</status>\n";
echo "    <errstr></errstr>\n";

echo "    <results>\n";
foreach ($results as $object)
{
    echo "      <tag>{$object->tag}</tag>\n";
}
echo "    </results>\n";

echo "</response>\n";
?>