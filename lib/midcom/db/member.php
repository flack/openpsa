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
