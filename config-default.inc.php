<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once 'tools/bootstrap.php';
$GLOBALS['midcom_config_local']['log_level'] = 5;
$GLOBALS['midcom_config_local']['log_filename'] = dirname(midgard_connection::get_instance()->config->logfilename) . '/midcom.log';
$GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
$GLOBALS['midcom_config_local']['auth_backend_simple_cookie_secure'] = false;
$GLOBALS['midcom_config_local']['toolbars_enable_centralized'] = false;