<?php
/**
 * @package midcom.services.at
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: entry.php 24773 2010-01-18 08:15:45Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to the at-job database entries
 * @package midcom.services.at
 */
class midcom_services_at_entry_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midcom_services_at_entry_db';

    /**
     * Unserialized form of argumentsstore
     *
     * @var array
     */
    var $arguments = array();

    /**
     * Empty constructor
     */
    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        return parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    /**
     * Makes sure $arguments is properly set
     *
     * @return boolean Always true
     */
    function _on_loaded()
    {
        $this->_unserialize_arguments();
        return true;
    }

    /**
     * Makes sure we have status set and arguments serialized
     *
     * @return boolean Always true
     */
    function _on_creating()
    {
        if (!$this->status)
        {
            $this->status = MIDCOM_SERVICES_AT_STATUS_SCHEDULED;
        }
        if (!$this->host)
        {
            $this->host = $_MIDGARD['host'];
        }
        $this->_serialize_arguments();
        return true;
    }

    /**
     * Makes sure we have arguments serialized
     *
     * @return boolean Always true
     */
    function _on_updating()
    {
        $this->_serialize_arguments();
        return true;
    }

    /**
     * Autopurge after delete
     */
    function _on_deleted()
    {
        if (!method_exists($this, 'purge'))
        {
            return;
        }
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
            $unserRet = @unserialize($this->_fix_serialization($this->argumentsstore));
            if ($unserRet === false)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Failed to unserialize argumentsstore', MIDCOM_LOG_WARN);
                debug_pop();
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
     * Fixes newline etc encoding issues in serialized data
     *
     * @param string $data The data to fix.
     * @return string $data with serializations fixed.
     */
    function _fix_serialization($data = null)
    {
        //Skip on empty data
        if (empty($data))
        {
            return $data;
        }

        $preg = '/s:([0-9]+):"(.*?)";/ms';
        preg_match_all($preg, $data, $matches);
        $cache = array();

        foreach ($matches[0] as $k => $origFullStr)
        {
              $origLen = $matches[1][$k];
              $origStr = $matches[2][$k];
              $newLen = strlen($origStr);
              if ($newLen != $origLen)
              {
                 $newFullStr="s:$newLen:\"$origStr\";";
                 //For performance we cache information on which strings have already been replaced
                 if (!array_key_exists($origFullStr, $cache))
                 {
                     $data = str_replace($origFullStr, $newFullStr, $data);
                     $cache[$origFullStr] = true;
                 }
              }
        }

        return $data;
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with entry objects, later we can add
     * restrictions on object level as necessary.
     *
     * @return array MidCOM privileges
     */
    function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['USERS']['midgard:create']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:update']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:read']    = MIDCOM_PRIVILEGE_ALLOW;
        return $privileges;
    }
}

/**
 * Another wrap level to make midcom_services_at_entry::new_query_builder() happy
 * @package midcom.services.at
 */
class midcom_services_at_entry extends midcom_services_at_entry_dba
{
}

?>