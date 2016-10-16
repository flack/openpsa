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
 * @package midcom.services.at
 */
class midcom_services_at_entry_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midcom_services_at_entry_db';

    public $_use_activitystream = false;
    public $_use_rcs = false;

    const SCHEDULED = 100;
    const RUNNING = 110;
    const FAILED = 120;

    /**
     * Unserialized form of argumentsstore
     *
     * @var array
     */
    public $arguments = array();

    /**
     * Makes sure $arguments is properly set
     */
    public function _on_loaded()
    {
        $this->_unserialize_arguments();
    }

    /**
     * Makes sure we have status set and arguments serialized
     *
     * @return boolean Always true
     */
    public function _on_creating()
    {
        if (!$this->status)
        {
            $this->status = self::SCHEDULED;
        }
        if (!$this->host)
        {
            $this->host = midcom_connection::get('host');
        }
        $this->_serialize_arguments();
        return true;
    }

    /**
     * Makes sure we have arguments serialized
     *
     * @return boolean Always true
     */
    public function _on_updating()
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
    function _unserialize_arguments()
    {
        $unserRet = @unserialize($this->argumentsstore);
        if ($unserRet === false)
        {
            //Unserialize failed (probably newline/encoding issue), try to fix the serialized string and unserialize again
            $unserRet = @unserialize(midcom_helper_misc::fix_serialization($this->argumentsstore));
            if ($unserRet === false)
            {
                debug_add('Failed to unserialize argumentsstore', MIDCOM_LOG_WARN);
                $this->arguments = array();
                return;
            }
        }
        $this->arguments = $unserRet;
    }

    /**
     * Serializes arguments to argumentsstore
     */
    function _serialize_arguments()
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
