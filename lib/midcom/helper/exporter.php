<?php
/**
 * @author tarjei huse
 * @package midcom.helper
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\introspection\helper;

/**
 * Exporter baseclass
 *
 * @package midcom.helper
 */
abstract class midcom_helper_exporter
{
    abstract public function array2data(array $array);

    abstract public function data2array($data);

    /**
     * Take an object and return an array of useful fields (removing private properties)
     *
     * @param midcom_core_dbaobject $object
     * @return array
     */
    public function object2array($object, $all_metadata_fields = false)
    {
        if (!is_object($object))
        {
            debug_add("Missing object needed as parameter.", MIDCOM_LOG_ERROR);
            return false;
        }

        $out = array();
        $fields = $this->_get_object_fields($object, $all_metadata_fields);

        foreach ($fields as $key)
        {
            if (substr($key, 0, 1) == '_' && $key != "__metadata")
            {
                // Remove private fields
                continue;
            }
            if (is_object($object->{$key}))
            {
                $out[$key] = $this->object2array($object->{$key}, $all_metadata_fields);
            }
            else
            {
                $out[$key] = $object->{$key};
            }
        }

        return $out;
    }

    /**
     * Take data from array and move it into an object
     *
     * @param array
     * @param midcom_core_dbaobject The object in question
     * @return object the updated object (not saved)
     */
    public function array2object(array $data, midcom_core_dbaobject $object)
    {
        if (!is_array($data))
        {
            debug_add("Invalid datatype");
            return false;
        }

        // set the object's values to the ones from the data
        $fields = $this->_get_object_fields($object);
        foreach ($fields as $field_name)
        {
            // skip private fields.
            if (substr($field_name, 0, 1) == '_')
            {
                continue;
            }

            // skip read_only fields
            if ($field_name == 'guid' || $field_name == 'id')
            {
                continue;
            }

            // TODO: decide what to do with object metadata
            if ($field_name == 'metadata')
            {
                continue;
            }

            if (isset($data[$field_name]))
            {
                $object->{$field_name} = $data[$field_name];
                continue;
            }

            // unset any other value that was there before.
            $object->{$field_name} = null;
        }
        return $object;
    }

    public function data2object(array $data, midcom_core_dbaobject $object)
    {
        return $this->array2object($data, $object);
    }

    protected function _get_object_fields($object, $all_metadata_fields = false)
    {
        // workaround for problem with _get_object_fields / instrospection helper returning only array("__object", "guid") and is missing all other fields under midgard2
        if ($all_metadata_fields && $object instanceof midcom_helper_metadata)
        {
            return array("guid", "created", "hidden", "deleted", "isapproved", "islocked");
        }

        if (method_exists($object, 'get_properties'))
        {
            // MidCOM DBA decorator object
            return $object->get_properties();
        }

        $helper = new helper;
        return $helper->get_all_properties($object);
    }

    /**
     * Get the correct classname
     *
     * @param object $object the object
     * @return string the mgdschmea classname
     */
    protected function _get_classname($object)
    {
        if (!empty($object->__mgdschema_class_name__))
        {
            return $object->__mgdschema_class_name__;
        }
        return get_class($object);
    }
}
