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
     */
    public function array2data(array $array) : string
    {
        foreach ($array as $key => $val) {
            if (is_object($val)) {
                $array[$key] = $this->object2array($val);
            }
        }
        return json_encode($array);
    }

    /**
     * Make an array out of some JSON
     *
     * @return array with attribute => key values
     */
    public function data2array(string $data) : array
    {
        return json_decode($data, true);
    }

    /**
     * Make JSON out of an object
     *
     * @param midcom_core_dbaobject $object
     */
    public function object2data($object) : string
    {
        $arr = $this->object2array($object);
        return json_encode($arr);
    }
}
