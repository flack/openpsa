<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Indexer client class
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_midcom_indexer extends midcom_services_indexer_client
{
    public function prepare_document(midcom_services_indexer_document &$document, midcom_helper_datamanager2_datamanager $dm)
    {
        $document->title = $dm->storage->object->get_label();
    }
}
?>