<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_cron_duplicates_mark extends midcom_baseclasses_components_cron_handler
{
    /**
     * Find possible duplicates and mark them
     */
    public function execute()
    {
        if (!$this->_config->get('enable_duplicate_search')) {
            debug_add('Duplicate search disabled, aborting', MIDCOM_LOG_INFO);
            return;
        }

        midcom::get()->auth->request_sudo($this->_component);

        $pfinder = new org_openpsa_contacts_duplicates_check_person;
        $pfinder->mark_all(false);
        $gfinder = new org_openpsa_contacts_duplicates_check_group;
        $gfinder->mark_all(false);

        midcom::get()->auth->drop_sudo();
    }
}
