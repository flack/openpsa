<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Indexer client class
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_midcom_indexer extends midcom_helper_datamanager2_indexer_client
{
    public function prepare_document(midcom_services_indexer_document &$document, midcom_helper_datamanager2_datamanager $dm)
    {
        if (is_a($dm->storage->object, 'org_openpsa_contacts_person_dba')) {
            $document->title = $dm->storage->object->name;
        }
    }
}
