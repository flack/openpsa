<?php
/**
 * @author tarjei huse
 * @package midcom.helper
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a simple class to move midgard objects to and from
 * xml.
 *
 * Usage:
 * To get data from xml:
 * $mapper = new midcom_helper_xml;
 * $data = "<midcom_db_topic><id>7</id><name>Test</name></midcom_db_topic>"
 * $object = new midcom_db_topic(7);
 * $object2 = $mapper->data2object($data, $object);
 * echo $object2->name ; // outputs Test
 *
 * To get data to xml:
 * $object = new midcom_db_topic(7);
 * $mapper = new midcom_helper_xml;
 * $xml = $mapper->object2data($object);
 * echo $xml ; // outputs: "<midcom_db_topic><id>7</id><name>Test</name></midcom_db_topic>"
 *
 * @package midcom.helper
 */
class midcom_helper_xml
{
    /**
     * Take xml and move it into an object
     *
     * @param array xmldata
     * @param midcom_core_dbaobject The object in question.
     * @return object the updated object (not saved)
     */
    public function data2object(array $data, midcom_core_dbaobject $object)
    {
        $fields = $object->get_properties();

        // set the object's values to the ones from xml.
        foreach ($fields as $field_name)
        {
            // skip private fields.
            if (substr($field_name, 0, 1) == '_')
            {
                continue;
            }

            // skip read_only fields
            if (   $field_name == 'guid'
                || $field_name == 'id')
            {
                continue;
            }

            if ($field_name == 'metadata')
            {
                // TODO: decide what to do with object metadata!!!
                continue;
            }

            if (isset($data[$field_name]))
            {
                $object->$field_name = $data[$field_name];
                continue;
            }

            // unset any other value that was there before.
            $object->{$field_name} = null;
        }
        return $object;
    }

    /**
     * Make an array out of some xml.
     *
     * Note, the function expects xml like this:
     * <objecttype><attribute>attribute_value</attribute></objecttype>
     * But it will not return the objecttype.
     *
     * @param string $data xml
     * @return array with attribute => key values.
     */
    function data2array ($data)
    {
        if (!is_string($data))
        {
            debug_add("Missing data cannot unserialize");
            return false;
        }

        return $this->_xml_to_array(new SimpleXMLIterator($data));
    }

    private function _xml_to_array(SimpleXMLIterator $sxi)
    {
        $data = array();
        foreach ($sxi as $key => $val)
        {
            if ($sxi->hasChildren())
            {
                $data[$key] = $this->_xml_to_array($val);
            }
            else
            {
                $val = trim($val);
                //TODO: This is mainly here for backward-compatibility. Its main effect
                // is that 0 is replaced by an empty string. The question is: Do we want/need this?
                if (!$val)
                {
                    $val = '';
                }
                $data[$key] = trim($val);
            }
        }
        return $data;
    }

    /**
     * Make XML out of an object loaded into datamanager
     *
     * @param midcom_helper_datamanager2_datamanager $datamanager
     * @return xmldata
     */
    function dm2data($datamanager, $fallback_label = 'default', $additional_data = array())
    {
        $content = $datamanager->get_content_xml();
        $content['guid'] = $datamanager->storage->object->guid;
        $label = $datamanager->schema->name;
        if ($label == 'default')
        {
            $label = $fallback_label;
        }

        $content = array_merge($content, $additional_data);

        return $this->array2data($content, $label);
    }

    /**
     * Make XML out of an array.
     *
     * @param array $array
     * @return xmldata
     */
    function array2data(array $array, $root_node = 'array', $prefix = '')
    {
        $data  = "{$prefix}<{$root_node}>\n";

        foreach ($array as $key => $field)
        {
            if (is_numeric($key))
            {
                $key = 'value';
            }

            if (empty($field))
            {
               $data .= "{$prefix}    <{$key}/>\n";
            }
            else if (is_object($field))
            {
                $data .= $this->object2data($field, "{$prefix}    ");
            }
            else if (is_array($field))
            {
                $data .= $this->array2data($field, $key, "{$prefix}    ") . "\n";
            }
            else if (   is_numeric($field)
                     || is_bool($field))
            {
                $data .= "{$prefix}    <{$key}>{$field}</{$key}>\n";
            }
            else
            {
                // String
                $data .= "{$prefix}    <{$key}><![CDATA[{$field}]]></{$key}>\n";
            }
        }

        $data .= "{$prefix}</{$root_node}>\n";

        return $data;
    }

    /**
     * Make XML out of an object.
     *
     * @param midcom_core_dbaobject $object
     * @return xmldata
     */
    function object2data($object, $prefix = '')
    {
        if (!is_object($object))
        {
            debug_add("Missing object needed as parameter.", MIDCOM_LOG_ERROR);
            return false;
        }
        if (method_exists($object, 'get_properties'))
        {
            // MidCOM DBA decorator object
            $fields = $object->get_properties();
        }
        else
        {
            $fields = array_keys(get_object_vars($object));
        }

        $classname = $this->_get_classname($object);

        if (!empty($object->guid))
        {
            $data = "{$prefix}<{$classname} id=\"{$object->id}\" guid=\"{$object->guid}\">\n";
        }
        else
        {
            $data = "{$prefix}<{$classname}>\n";
        }

        foreach ($fields as $key)
        {
            if (substr($key, 0, 1) == '_')
            {
                // Remove private fields
                continue;
            }
            if (is_object($object->$key))
            {
                $data .= $this->object2data($object->$key, "{$prefix}    ");
            }

            else if (   is_numeric($object->$key)
                     || is_null($object->$key)
                     || is_bool($object->$key))
            {
                $data .= "{$prefix}    <{$key}>{$object->$key}</{$key}>\n";
            }
            else
            {
                $data .= "{$prefix}    <{$key}><![CDATA[{$object->$key}]]></{$key}>\n";
            }
        }

        $data .= "{$prefix}</{$classname}>";
        return $data;
    }

    /**
     * Get the correct classname
     *
     * @param object $object the object
     * @return string the mgdschmea classname
     */
    private function _get_classname( $object)
    {
        if (!empty($object->__mgdschema_class_name__))
        {
            return $object->__mgdschema_class_name__;
        }
        return get_class($object);
    }
}
?>