<?php
/**
 * @package org.openpsa.contacts
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for duplicate support
 *
 * @package org.openpsa.contacts
 */
interface org_openpsa_contacts_duplicates_support
{
    /**
     * Returns information about which classes should be processed in what way
     *
     * @param string $object_mode 'person' or 'group'
     * @param string $merge_mode 'all' or 'future'
     * @return array Merge settings
     */
    public function get_merge_configuration($object_mode, $merge_mode);
}
