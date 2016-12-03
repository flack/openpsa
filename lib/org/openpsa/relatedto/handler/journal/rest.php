<?php
/**
 * @package org.openpsa.relatedto
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * rest entry handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_journal_rest extends midcom_baseclasses_components_handler_rest
{
    public function get_object_classname()
    {
        return "org_openpsa_relatedto_journal_entry_dba";
    }
}
