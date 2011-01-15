<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Simple default topic hierarchy setup for OpenPSA
 *
 * @package midcom
 */
function openpsa_prepare_topics()
{
    $openpsa_topics = array
    (
        'Calendar' => 'org.openpsa.calendar',
        'Contacts' => 'org.openpsa.contacts',
        'Documents' => 'org.openpsa.documents',
        'Expenses' => 'org.openpsa.expenses',
        'Invoices' => 'org.openpsa.invoices',
        'Products' => 'org.openpsa.products',
        'Projects' => 'org.openpsa.projects',
        'Reports' => 'org.openpsa.reports',
        'Sales' => 'org.openpsa.sales',
        'Wiki' => 'net.nemein.wiki',
    );
    $qb = new midgard_query_builder('midgard_topic');
    $qb->add_constraint('name', '=', 'openpsa');
    $qb->add_constraint('up', '=', 0);
    $topics = $qb->execute();
    if ($topics)
    {
        return $topics[0]->guid;
    }

    // Create a new root topic for OpenPSA
    $root_topic = new midgard_topic();
    $root_topic->name = 'openpsa';
    $root_topic->component = 'org.openpsa.mypage';
    $root_topic->extra = 'OpenPSA';
    if (!$root_topic->create())
    {
        throw new Exception('Failed to create root topic for OpenPSA: ' . midgard_connection::get_instance()->get_error_string());
    }

    foreach ($openpsa_topics as $title => $component)
    {
        $topic = new midgard_topic();
        $topic->name = strtolower($title);
        $topic->component = $component;
        $topic->extra = $title;
        $topic->up = $root_topic->id;
        $topic->create();
    }

    return $root_topic->guid;
}

$GLOBALS['midcom_config_local']['log_level'] = 5;
$GLOBALS['midcom_config_local']['log_filename'] = dirname(midgard_connection::get_instance()->config->logfilename) . '/midcom.log';
$GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
$GLOBALS['midcom_config_local']['auth_backend_simple_cookie_secure'] = false;
$GLOBALS['midcom_config_local']['toolbars_enable_centralized'] = false;
?>