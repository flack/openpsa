<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\indexer\client;
use midcom\datamanager\datamanager;

/**
 * Indexer client class
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_midcom_indexer extends client
{
    public function prepare_document(midcom_services_indexer_document &$document, datamanager $dm)
    {
        $object = $dm->get_storage()->get_value();
        $document->title = $object->get_label();
        if (is_a($object, org_openpsa_projects_task_dba::class)) {
            $values = $dm->get_content_html();
            $document->abstract = $values['start'] . ' - ' . $values['end'] . ', ' . $values['status'];
            $document->abstract .= ' ' . substr($values['description'], 0, 200);
        }
    }
}
