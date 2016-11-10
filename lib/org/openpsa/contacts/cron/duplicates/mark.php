<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_cron_duplicates_mark extends midcom_baseclasses_components_cron_handler
{
    /**
     * Find possible duplicates and mark them
     */
    public function _on_execute()
    {
        if (!$this->_config->get('enable_duplicate_search'))
        {
            debug_add('Duplicate search disabled, aborting', MIDCOM_LOG_INFO);
            return;
        }

        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        ignore_user_abort();

        $dfinder = new org_openpsa_contacts_duplicates_check();
        $dfinder->mark_all(false);

        midcom::get()->auth->drop_sudo();
    }
}
