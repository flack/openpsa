<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Membership record with framework support.
 *
 * @package midcom.db
 */
class midcom_db_member extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_member';

    public $_use_rcs = false;

    public function get_label()
    {
        try {
            $person = new midcom_db_person($this->uid);
            $grp = new midcom_db_group($this->gid);
        } catch (midcom_error $e) {
            $e->log();
            return 'Invalid membership record';
        }
        return sprintf(midcom::get()->i18n->get_string('%s in %s', 'midcom'), $person->name, $grp->official);
    }

    /**
     * Invalidate person's cache when a member record changes
     */
    private function _invalidate_person_cache()
    {
        if (!$this->uid) {
            return;
        }
        try {
            $person = new midcom_db_person($this->uid);
        } catch (midcom_error $e) {
            return;
        }
        midcom::get()->cache->invalidate($person->guid);
    }

    public function _on_creating()
    {
        return $this->_check_gid();
    }

    public function _on_updating()
    {
        return $this->_check_gid();
    }

    private function _check_gid()
    {
        // Allow root group membership creation only for admins
        if (   $this->gid == 0
            && !midcom::get()->auth->admin) {
            debug_add("Group #0 membership creation only allowed for admins");
            debug_print_function_stack('Forbidden ROOT member creation called from');
            return false;
        }

        return true;
    }

    public function _on_created()
    {
        $this->_invalidate_person_cache();
    }

    public function _on_updated()
    {
        $this->_invalidate_person_cache();
    }

    public function _on_deleted()
    {
        $this->_invalidate_person_cache();
    }
}
