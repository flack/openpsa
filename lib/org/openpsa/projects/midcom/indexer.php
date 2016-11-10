<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Indexer client class
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_midcom_indexer extends midcom_helper_datamanager2_indexer_client
{
    public function prepare_document(midcom_services_indexer_document &$document, midcom_helper_datamanager2_datamanager $dm)
    {
        $document->title = $dm->storage->object->get_label();
        if (is_a($dm->storage->object, 'org_openpsa_projects_task_dba')) {
            $values = $dm->get_content_csv();
            $document->abstract = $values['start'] . ' - ' . $values['end'] . ', ' . $this->_l10n->get($values['status']);
            $document->abstract .= ' ' . substr($values['description'], 0, 200);
        }
    }
}
