<?php
/**
 * Handler for autocomplete searches
 *
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

$handler = new midcom_helper_datamanager2_ajax_autocomplete($_REQUEST);

// Common headers
midcom::get()->cache->content->content_type('application/json');
midcom::get()->header('Content-type: application/json; charset=UTF-8');

echo json_encode($handler->get_results());

midcom::get()->finish();