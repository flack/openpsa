<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Style record with framework support.
 *
 * The uplink is the owning Style.
 *
 * @property string $name Path name of the style
 * @property integer $up Style the style is under
 * @package midcom.db
 */
class midcom_db_style extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_style';

    /**
     * Returns the path of the style described by $id.
     *
     * @param int $id    Style id to look up path for
     */
    public static function path_from_id(int $id) : string
    {
        static $path_cache = [];
        if (isset($path_cache[$id])) {
            return $path_cache[$id];
        }
        // Construct the path
        $path_parts = [];
        $original_id = $id;

        try {
            while (($style = new self($id))) {
                $path_parts[] = $style->name;
                $id = $style->up;

                if ($style->up == 0) {
                    // Toplevel style
                    break;
                }
            }
        } catch (midcom_error $e) {
        }

        $path_parts = array_reverse($path_parts);

        $path_cache[$original_id] = '/' . implode('/', $path_parts);

        return $path_cache[$original_id];
    }

    /**
     * Returns the id of the style described by $path.
     *
     * Note: $path already includes the element name, so $path looks like
     * "/rootstyle/style/style/element".
     *
     * @todo complete documentation
     * @param string $path      The path to retrieve
     * @param int $rootstyle    ???
     * @return    int ID of the matching style or 0
     */
    public static function id_from_path($path, $rootstyle = 0) : int
    {
        if (substr($path, 0, 6) === 'theme:') {
            return 0;
        }
        static $cached = [];

        $cache_key = $rootstyle . '::' . $path;

        if (array_key_exists($cache_key, $cached)) {
            return $cached[$cache_key];
        }

        $path = preg_replace("/^\/(.*)/", "$1", $path); // leading "/"
        $cached[$cache_key] = 0;
        $current_style = 0;

        $path_array = array_filter(explode('/', $path));
        if (!empty($path_array)) {
            $current_style = $rootstyle;
        }

        foreach ($path_array as $path_item) {
            $mc = midgard_style::new_collector('up', $current_style);
            $mc->set_key_property('guid');
            $mc->add_value_property('id');
            $mc->add_constraint('name', '=', $path_item);
            $mc->execute();
            $styles = $mc->list_keys();

            if (!empty($styles)) {
                $style_guid = key($styles);
                $current_style = $mc->get_subkey($style_guid, 'id');
                midcom::get()->cache->content->register($style_guid);
            }
        }

        $cached[$cache_key] = $current_style;

        return $cached[$cache_key];
    }
}
