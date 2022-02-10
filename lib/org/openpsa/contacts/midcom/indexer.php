<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\indexer\client;
use midcom\datamanager\datamanager;

/**
 * Indexer client class
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_midcom_indexer extends client
{
    public function prepare_document(midcom_services_indexer_document $document, datamanager $dm)
    {
        if ($dm->get_storage()->get_value() instanceof org_openpsa_contacts_person_dba) {
            $document->title = $dm->get_storage()->get_value()->name;
        }
    }
}
