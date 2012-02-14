<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Parameter record with framework support.
 *
 * The uplink is the parentguid parameter.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the get_by*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable through the statically callable new_query_builder() DBA methods.
 *
 * @package midcom.db
 * @see midcom_services_dbclassloader
 */
class midcom_db_parameter extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_parameter';

    public function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        parent::__construct($id);
    }

    static function new_query_builder()
    {
        return midcom::get('dbfactory')->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return midcom::get('dbfactory')->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return midcom::get('dbfactory')->get_cached(__CLASS__, $src);
    }

    function get_parent_guid_uncached()
    {
        return $this->parentguid;
    }

    /**
     * Returns the Parent of the Parameter.
     *
     * @return MidgardObject Parent object or null if there is none.
     */
    public static function get_parent_guid_uncached_static($guid, $classname = __CLASS_)
    {
        $mc = new midgard_collector('midgard_parameter', 'guid', $guid);
        $mc->set_key_property('parentguid');
        $mc->execute();
        $link_values = $mc->list_keys();
        if (!$link_values)
        {
            return null;
        }

        foreach ($link_values as $key => $value)
        {
            return $key;
        }
    }

    /**
     * Helper function to read a parameter without loading the corresponding object.
     * This is primarily for improving performance, so the function does not check
     * for privileges.
     *
     * @param string $objectguid The object's GUID
     * @param string $domain The parameter's domain
     * @param string $name The parameter to look for
     */
    public static function get_by_objectguid($objectguid, $domain, $name)
    {
        static $parameter_cache = array();

        if (!isset($parameter_cache[$objectguid]))
        {
            $parameter_cache[$objectguid] = array();
        }

        if (isset($parameter_cache[$objectguid][$name]))
        {
            return $parameter_cache[$objectguid][$name];
        }

        $mc = midgard_parameter::new_collector('parentguid', $objectguid);
        $mc->set_key_property('value');
        $mc->add_constraint('name', '=', $name);
        $mc->add_constraint('domain', '=', $domain);
        $mc->set_limit(1);
        $mc->execute();
        $parameters = $mc->list_keys();

        if (count($parameters) == 0)
        {
            $parameter_cache[$objectguid][$name] = null;
            return $parameter_cache[$objectguid][$name];
        }

        $parameter_cache[$objectguid][$name] = key($parameters);

        return $parameter_cache[$objectguid][$name];
    }

    public function get_label()
    {
        return "{$this->domain} {$this->name}";
    }
}
?>