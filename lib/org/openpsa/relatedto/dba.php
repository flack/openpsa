<?php
/**
 * @package org.openpsa.relatedto
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @property string $fromComponent
 * @property string $fromGuid
 * @property string $fromClass
 * @property string $fromExtra
 * @property string $toComponent
 * @property string $toGuid
 * @property string $toClass
 * @property string $toExtra
 * @property integer $status
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_relatedto';
    public $_use_rcs = false;

    const SUSPECTED = 100;
    const CONFIRMED = 120;
    const NOTRELATED = 130;

    public function _on_creating()
    {
        $this->check_status();
        //PONDER: Should we call check_db() here and prevent creation of multiple very similar links ??
        return true;
    }

    public function _on_loaded()
    {
        $this->check_status();
    }

    public function _on_updating()
    {
        $this->check_status();
        return true;
    }

    private function check_status()
    {
        if (!$this->status) {
            $this->status = self::SUSPECTED;
        }
    }

    /**
     * Check database for essentially same relatedto object and returns GUID if found
     */
    public function check_db($check_status = true) : ?string
    {
        $mc = self::new_collector('toGuid', $this->toGuid);
        $mc->add_constraint('fromClass', '=', $this->fromClass);
        $mc->add_constraint('toClass', '=', $this->toClass);
        $mc->add_constraint('fromGuid', '=', $this->fromGuid);
        $mc->add_constraint('fromComponent', '=', $this->fromComponent);
        $mc->add_constraint('toComponent', '=', $this->toComponent);
        if ($check_status) {
            $mc->add_constraint('status', '=', $this->status);
        }
        $mc->set_limit(1);
        return key($mc->list_keys());
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with relatedto objects, later we can add
     * restrictions on object level as necessary.
     */
    public function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['USERS']['midgard:create']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:update']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:read']    = MIDCOM_PRIVILEGE_ALLOW;
        return $privileges;
    }
}
