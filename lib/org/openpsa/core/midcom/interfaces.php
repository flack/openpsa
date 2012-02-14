<?php
/**
 * @package org.openpsa.core
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA core stuff
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_interface extends midcom_baseclasses_components_interface
{
    public function _on_initialize()
    {
        $this->define_constants();
        $this->set_acl_options();

        return true;
    }

    private function define_constants()
    {
        //Constant versions of wgtype bitmasks
        define('ORG_OPENPSA_WGTYPE_NONE', 0);
        define('ORG_OPENPSA_WGTYPE_INACTIVE', 1);
        define('ORG_OPENPSA_WGTYPE_ACTIVE', 3);

        //Constants for ACL shortcuts
        define('ORG_OPENPSA_ACCESSTYPE_PRIVATE', 100);
        define('ORG_OPENPSA_ACCESSTYPE_WGPRIVATE', 101);
        define('ORG_OPENPSA_ACCESSTYPE_PUBLIC', 102);
        define('ORG_OPENPSA_ACCESSTYPE_AGGREGATED', 103);
        define('ORG_OPENPSA_ACCESSTYPE_WGRESTRICTED', 104);
        define('ORG_OPENPSA_ACCESSTYPE_ADVANCED', 105);

        //org.openpsa.documents object types
        define('ORG_OPENPSA_OBTYPE_DOCUMENT', 3000);
        //org.openpsa.documents document status
        define('ORG_OPENPSA_DOCUMENT_STATUS_DRAFT', 4000);
        define('ORG_OPENPSA_DOCUMENT_STATUS_FINAL', 4001);
        define('ORG_OPENPSA_DOCUMENT_STATUS_REVIEW', 4002);

        //org.openpsa.calendar object types
        define('ORG_OPENPSA_OBTYPE_EVENT', 5000);
        define('ORG_OPENPSA_OBTYPE_EVENTPARTICIPANT', 5001);
        define('ORG_OPENPSA_OBTYPE_EVENTRESOURCE', 5002);

        //org.openpsa.reports object types
        define('ORG_OPENPSA_OBTYPE_REPORT', 7000);
        define('ORG_OPENPSA_OBTYPE_REPORT_TEMPORARY', 7001);
    }

    /**
     * Make the ACL selection array available to all components
     */
    private function set_acl_options()
    {
        if (!array_key_exists('org_openpsa_core_acl_options', $GLOBALS))
        {
            $GLOBALS['org_openpsa_core_acl_options'] = array
            (
                ORG_OPENPSA_ACCESSTYPE_WGRESTRICTED => $this->_l10n->get('workgroup restricted'),
                ORG_OPENPSA_ACCESSTYPE_WGPRIVATE => $this->_l10n->get('workgroup private'),
                ORG_OPENPSA_ACCESSTYPE_PRIVATE => $this->_l10n->get('private'),
                ORG_OPENPSA_ACCESSTYPE_PUBLIC => $this->_l10n->get('public'),
                ORG_OPENPSA_ACCESSTYPE_AGGREGATED => $this->_l10n->get('aggregated'),
            );
        }
    }
}
?>