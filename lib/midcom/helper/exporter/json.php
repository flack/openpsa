<?php
/**
 * @author tarjei huse
 * @package midcom.helper
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Move midgard objects to and from JSON
 *
 * @package midcom.helper
 */
class midcom_helper_exporter_json extends midcom_helper_exporter
{
    /**
     * Make JSON out of an array.
     *
     * @param array $array
     * @return string
     */
    public function array2data(array $array)
    {
        foreach ($array as $key => $val)
        {
            if (is_object($array[$key]))
            {
                $array[$key] = $this->object2array($val, true);
            }
        }
        return json_encode($array);
    }

    /**
     * Make an array out of some JSON
     *
     * @param string $data
     * @return array with attribute => key values
     */
    public function data2array($data)
    {
        if (!is_string($data))
        {
            debug_add("Missing data cannot unserialize");
            return false;
        }
        return json_decode($data, true);
    }

    /**
     * Make JSON out of an object
     *
     * @param midcom_core_dbaobject $object
     * @return string
     */
    public function object2data($object)
    {
        $arr = $this->object2array($object, true);
        return json_encode($arr);
    }
}
