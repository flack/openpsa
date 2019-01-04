<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the class that defines which URLs should be handled by this module.
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_viewer extends midcom_baseclasses_components_viewer
{
    public function _on_handle($handler, array $args)
    {
        midcom::get()->auth->require_valid_user();
    }
}
