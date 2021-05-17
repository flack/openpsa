<?php
/**
 * @package midcom.services.at
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to the at-job database entries
 *
 * @property integer $status
 * @property integer $start
 * @property string $component
 * @property string $method
 * @property string $argumentsstore
 * @property integer $host
 * @package midcom.services.at
 */
class midcom_services_at_entry_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midcom_services_at_entry_db';

    public $_use_rcs = false;

    const SCHEDULED = 100;
    const RUNNING = 110;
    const FAILED = 120;

    /**
     * Unserialized form of argumentsstore
     *
     * @var array
     */
    public $arguments = [];

    /**
     * Makes sure $arguments is properly set
     */
    public function _on_loaded()
    {
        $this->_unserialize_arguments();
    }

    /**
     * Makes sure we have status set and arguments serialized
     */
    public function _on_creating() : bool
    {
        if (!$this->status) {
            $this->status = self::SCHEDULED;
        }
        $this->_serialize_arguments();
        return true;
    }

    /**
     * Makes sure we have arguments serialized
     */
    public function _on_updating() : bool
    {
        $this->_serialize_arguments();
        return true;
    }

    /**
     * Autopurge after delete
     */
    public function _on_deleted()
    {
        $this->purge();
    }

    /**
     * Unserializes argumentsstore to arguments
     */
    private function _unserialize_arguments()
    {
        $unserRet = unserialize($this->argumentsstore);
        if ($unserRet === false) {
            debug_add('Failed to unserialize argumentsstore', MIDCOM_LOG_ERROR);
            $this->arguments = [];
            return;
        }
        $this->arguments = $unserRet;
    }

    /**
     * Serializes arguments to argumentsstore
     */
    private function _serialize_arguments()
    {
        $this->argumentsstore = serialize($this->arguments);
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with entry objects, later we can add
     * restrictions on object level as necessary.
     *
     * @return array MidCOM privileges
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
