<?php


/**
 * Created on Jan 12, 2006
 * @author tarjei huse
 * @package midcom.helper.xml
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 */

/**
 * This is a simple class to move midgard objects to and from
 * xml.
 *
 * @package midcom.helper.xml
 *
 * Usage:
 * To get data from xml:
 * $mapper = new midcom_helper_xml_objectmapper;
 * $data = "<midcom_db_topic><id>7</id><name>Test</name></midcom_db_topic>"
 * $object = new midcom_db_topic(7);
 * $object2 = $mapper->data2object($data, $object);
 * echo $object2->name ; // outputs Test
 *
 * To get data to xml:
 * $object = new midcom_db_topic(7);
 * $mapper = new midcom_helper_xml_objectmapper;
 * $xml = $mapper->object2data($object);
 * echo $xml ; // outputs: "<midcom_db_topic><id>7</id><name>Test</name></midcom_db_topic>"
 *
 */
class midcom_helper_xml_objectmapper
{

    /**
     * The classname of the last read object
     * @var string classname
     * @access public
     */
    var $classname = "";
    /**
     * The errorstring
     * @access public
     * @var string
     */
    var $errstr = "";

    /**
     * Take xml and move it into an object
     * @param array xmldata
     * @param the object in question.
     * @return object the updated object (not saved)
     */
    function data2object($data, $object)
    {
        if (!is_array($data))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Missing data cannot unserialize");
            debug_pop();
            return false;
        }
        
        if (!is_object($object))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Missing object, cannot unserialize");
            debug_pop();
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
                && $field_name == 'id')
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
     * @param xml
     * @return array with attribute => key values.
     */
    function data2array ($data)
    {
        if (!is_string($data))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            if (!is_string($data))
            {
                debug_add("Missing data cannot unserialize");
            }
            debug_pop();
            return false;
        }

        $parser = new midcom_helper_xml_toarray();

        $array = $parser->parse($data);

        if (!$array)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Error on parsing XML:  ".$parser->errstr);
            debug_add("Data: $data");
            debug_pop();
            return false;
        }
        /* the xml is prefixed with either the old midcom class or the new one. We solve this
         * by just jumping over it as we already got the object.
         */
        $this->classname = key($array);

        // move the values from _content to the index.
        foreach ($array[$this->classname] as $fieldname => $value)
        {
            if (!isset($value['_content']))
            {
                continue;
                // No content, skip
            }
            if ($fieldname == 'attributes')
            {
                continue;
                // We're not interested in attribs
            }

            $value['_content'] = trim($value['_content']);

            if (   is_array($value)
                && empty($value['_content'])
                && count($value) > 1)
            {
                // This should be dealt as an array
                $array[$this->classname][$fieldname] = array();
                foreach ($value as $subfield => $subvalue)
                {
                    if (!isset($subvalue['_content']))
                    {
                        continue;
                    }

                    $array[$this->classname][$fieldname][$subfield] = $subvalue['_content'];
                }
            }
            elseif (is_array($value)
                && array_key_exists('_content', $value))
            {
                $array[$this->classname][$fieldname] = $value['_content'];
            }
            else
            {
                unset($array[$this->classname][$fieldname]);
            }
        }

        return $array[$this->classname];
    }

    /**
     * Make XML out of an object loaded into datamanager
     * @param midcom_helper_datamanager2_datamanager
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
     * @param array
     * @return xmldata
     */
    function array2data($array, $root_node = 'array', $prefix = '')
    {
        if (!is_array($array))
        {
            debug_push(__CLASS__, __FUNCTION__);
            debug_add("This function must get an array as its parameter not: " . gettype($array));
            $this->errstr = "This function must get an array as its parameter not: " . gettype($array);
            debug_pop();
            return false;
        }

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
            elseif (is_object($field))
            {
                $data .= $this->object2data($field, null, "{$prefix}    ");
            }
            elseif (is_array($field))
            {
                $data .= $this->array2data($field, $key, "{$prefix}    ") . "\n";
            }
            elseif (   is_numeric($field)
                    || is_null($field)
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
     * Make xml out of an object.
     * @param object
     * @return xmldata
     */
    function object2data($object, $classname = null, $prefix = '')
    {
        if (!is_object($object))
        {
            debug_push(__CLASS__, __FUNCTION__);
            debug_add("This function must get an object as its parameter not: " . gettype($object));
            $this->errstr = "This function must get an object as its parameter not: " . gettype($object);
            debug_pop();
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

        // Remove private fields
        foreach ($fields as $id => $key)
        {
            if (substr($key, 0, 1) == '_')
            {
                unset($fields[$id]);
            }
        }

        if (is_null($classname))
        {
            $classname = $this->_get_classname($object);
        }

        if (isset($object->guid))
        {
            $data = "{$prefix}<{$classname} id=\"{$object->id}\" guid=\"{$object->guid}\">\n";
        }
        else
        {
            $data = "{$prefix}<{$classname}>\n";
        }

        foreach ($fields as $key)
        {
            if (is_object($object->$key))
            {;
                $data .= $this->object2data($object->$key, null, "{$prefix}    ");
            }

            elseif (   is_numeric($object->$key)
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
     * @param object the object
     * @return string the mgdschmea classname
     *
     */
    function _get_classname( $object)
    {
        $vars = get_object_vars($object);
        if (array_key_exists( '__mgdschema_class_name__', $vars) )
        {
            return $object->__mgdschema_class_name__;
        }
        return get_class($object);
    }
}
