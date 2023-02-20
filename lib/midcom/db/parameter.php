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
 * @property string $domain Namespace of the parameter
 * @property string $name Key of the parameter
 * @property string $value Value of the parameter
 * @property string $parentguid GUID of the object the parameter extends
 * @package midcom.db
 */
class midcom_db_parameter extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'midgard_parameter';

    public bool $_use_rcs = false;

    /**
     * Read a parameter without loading the corresponding object.
     * This is primarily for improving performance, so the function does not check
     * for privileges.
     */
    public static function get_by_objectguid(string $objectguid, string $domain, string $name)
    {
        static $parameter_cache = [];
        $cache_key = $objectguid . '::' . $domain . '::' . $name;

        if (!array_key_exists($cache_key, $parameter_cache)) {

            $mc = midgard_parameter::new_collector('parentguid', $objectguid);
            $mc->set_key_property('value');
            $mc->add_constraint('name', '=', $name);
            $mc->add_constraint('domain', '=', $domain);
            $mc->set_limit(1);
            $mc->execute();

            $parameter_cache[$cache_key] = key($mc->list_keys());
        }
        return $parameter_cache[$cache_key];
    }

    public function get_label() : string
    {
        return "{$this->domain} {$this->name}";
    }
}
