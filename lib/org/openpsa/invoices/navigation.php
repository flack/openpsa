<?php
/**
 * @package org.openpsa.invoices
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.invoices NAP interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_navigation extends midcom_baseclasses_components_navigation
{
    public function get_leaves()
    {
        return [
            "{$this->_topic->id}:scheduled" => [
                MIDCOM_NAV_URL => "scheduled/",
                MIDCOM_NAV_NAME => $this->_l10n->get('scheduled invoices'),
            ],
            "{$this->_topic->id}:projects" => [
                MIDCOM_NAV_URL => "projects/",
                MIDCOM_NAV_NAME => $this->_l10n->get('project invoicing'),
            ],
        ];
    }
}
