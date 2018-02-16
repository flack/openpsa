<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Wrapper for midcom_db_topic
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_directory extends midcom_db_topic
{
    public function _on_creating()
    {
        $generator = midcom::get()->serviceloader->load(midcom_core_service_urlgenerator::class);
        $this->name = $generator->from_string($this->extra);
        return parent::_on_creating();
    }

    public function _on_updated()
    {
        $this->_update_parent_timestamp();

        $ownerwg = $this->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg');
        $accesstype = $this->get_parameter('org.openpsa.core', 'orgOpenpsaAccesstype');

        if (   $ownerwg
            && $accesstype) {
            // Sync the object's ACL properties into MidCOM ACL system
            $sync = new org_openpsa_core_acl_synchronizer();
            $sync->write_acls($this, $ownerwg, $accesstype);
        }
    }

    public function _on_created()
    {
        $this->_update_parent_timestamp();
    }

    public function _on_deleted()
    {
        $this->_update_parent_timestamp();
    }

    private function _update_parent_timestamp()
    {
        $parent = $this->get_parent();
        if (   $parent
            && $parent->component == 'org.openpsa.documents') {
            midcom::get()->auth->request_sudo('org.openpsa.documents');

            $parent = new org_openpsa_documents_directory($parent);
            $parent->_use_rcs = false;
            $parent->_use_activitystream = false;
            $parent->update();

            midcom::get()->auth->drop_sudo();
        }
    }
}
