<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\storage\connection;
use midgard\portable\api\mgdobject;
use Doctrine\Common\Util\ClassUtils;

/**
 * The Grand Unified Reflector
 *
 * @package midcom.helper.reflector
 */
class midcom_helper_reflector extends midgard_reflection_property
{
    use midcom_baseclasses_components_base;

    public string $mgdschema_class = '';

    private static array $_cache = [
        'l10n' => [],
        'instance' => [],
        'title' => [],
        'name' => [],
        'fieldnames' => [],
        'object_icon_map' => null,
        'create_type_map' => null
    ];

    /**
     * Takes classname or object, resolves MgdSchema root class automagically
     *
     * @param string|mgdobject $src classname or object
     */
    public function __construct($src)
    {
        $this->_component = 'midcom.helper.reflector';

        // Resolve root class name
        $this->mgdschema_class = self::resolve_baseclass($src);

        // Instantiate midgard reflector
        parent::__construct($this->mgdschema_class);
    }

    /**
     * Get cached reflector instance
     *
     * @return static
     */
    public static function get(string|object $src) : self
    {
        $identifier = static::class . (is_object($src) ? $src::class : $src);

        return self::$_cache['instance'][$identifier] ??= new static($src);
    }

    /**
     * Get object's (mgdschema) fieldnames.
     */
    public static function get_object_fieldnames(object $object) : array
    {
        $classname = $object::class;
        $metadata = false;

        if (midcom::get()->dbclassloader->is_midcom_db_object($object)) {
            $classname = $object->__mgdschema_class_name__;
        } elseif ($object instanceof midcom_helper_metadata) {
            $metadata = true;
            $classname = $object->object->__mgdschema_class_name__;
        }

        if (is_subclass_of($classname, mgdobject::class)) {
            $cm = connection::get_em()->getClassMetadata($classname);
            return $cm->get_schema_properties($metadata);
        }
        return array_keys(get_object_vars($object));
    }

    /**
     * Gets a midcom_helper_l10n instance for component governing the type
     */
    public function get_component_l10n() : midcom_services_i18n_l10n
    {
        if (!isset(self::$_cache['l10n'][$this->mgdschema_class])) {
            if ($component = midcom::get()->dbclassloader->get_component_for_class($this->mgdschema_class)) {
                self::$_cache['l10n'][$this->mgdschema_class] = $this->_i18n->get_l10n($component);
            } else {
                debug_add("Could not resolve component for class {$this->mgdschema_class}, using our own l10n", MIDCOM_LOG_INFO);
                self::$_cache['l10n'][$this->mgdschema_class] = $this->_l10n;
            }
        }

        return self::$_cache['l10n'][$this->mgdschema_class];
    }

    /**
     * Get the localized label of the class
     *
     * @todo remove any hardcoded class names/prefixes
     */
    public function get_class_label() : string
    {
        $component_l10n = $this->get_component_l10n();
        $use_classname = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($this->mgdschema_class) ?: $this->mgdschema_class;
        $use_classname = preg_replace('/_(db|dba)$/', '', $use_classname);

        $label = $component_l10n->get($use_classname);
        if ($label == $use_classname) {
            // Class string not localized, try Bergie's way to pretty-print
            $classname_parts = explode('_', $use_classname);
            if (count($classname_parts) >= 3) {
                // Drop first two parts of class name
                array_shift($classname_parts);
                array_shift($classname_parts);
            }
            // FIXME: Remove hardcoded class prefixes
            $use_label = preg_replace('/(openpsa|notifications)_/', '', implode('_', $classname_parts));

            $use_label = str_replace('_', ' ', $use_label);
            $label = $component_l10n->get($use_label);
            if ($use_label == $label) {
                $label = ucwords($use_label);
            }
        }
        return $label;
    }

    /**
     * Get property name to use as label
     */
    public function get_label_property() : string
    {
        $midcom_class = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($this->mgdschema_class);
        $obj = ($midcom_class) ? new $midcom_class : new $this->mgdschema_class;

        if (method_exists($obj, 'get_label_property')) {
            return $obj->get_label_property();
        }
        return $this->get_property('title', $obj) ??
            $this->get_property('name', $obj) ??
            'guid';
    }

    /**
     * Get the object label property value
     */
    public function get_object_label(object $object) : ?string
    {
        if ($object instanceof mgdobject) {
            try {
                $obj = midcom::get()->dbfactory->convert_midgard_to_midcom($object);
            } catch (midcom_error) {
                return null;
            }
        } else {
            $obj = $object;
        }
        if (method_exists($obj, 'get_label')) {
            return $obj->get_label();
        }

        $properties = array_flip($obj->get_properties());
        if (empty($properties)) {
            debug_add("Could not list object properties, aborting", MIDCOM_LOG_ERROR);
            return null;
        }
        if (isset($properties['title'])) {
            return $obj->title;
        }
        if (isset($properties['name'])) {
            return $obj->name;
        }
        if ($obj->id > 0) {
            return $this->get_class_label() . ' #' . $obj->id;
        }
        return '';
    }

    /**
     * Get the name of the create icon image
     */
    public static function get_create_icon(string $type) : string
    {
        if (method_exists($type, 'get_create_icon')) {
            // class has static method to tell us the answer ? great !
            return $type::get_create_icon();
        }
        return self::get_icon($type, self::resolve_baseclass($type), 'create_type');
    }

    /**
     * heuristics magic (instead of adding something here, take a look at
     * config keys "create_type_magic" and "object_icon_magic")
     */
    private static function get_icon(string $object_class, string $object_baseclass, string $mode) : string
    {
        self::$_cache[$mode . '_map'] ??= self::_get_icon_map($mode . '_magic', $mode === 'create_type' ? 'file-o' : 'file');
        $map = self::$_cache[$mode . '_map'];

        return match (true) {
            isset($map[$object_class]) => $map[$object_class],

            isset($map[$object_baseclass]) => $map[$object_baseclass],

            str_contains($object_class, 'person') => $mode === 'create_type' ? 'user-o' : 'user',

            str_contains($object_class, 'event') => 'calendar-o',

            str_contains($object_class, 'member'),
            str_contains($object_class, 'organization'),
            str_contains($object_class, 'group') => 'users',

            str_contains($object_class, 'element') => 'file-code-o',

            default => $map['__default__']
        };
    }

    /**
     * Get the object icon
     */
    public static function get_object_icon(object $obj) : string
    {
        if (method_exists($obj, 'get_icon')) {
            // object knows it's icon, how handy!
            $icon = $obj->get_icon();
        } else {
            $icon = self::get_icon($obj::class, self::resolve_baseclass($obj), 'object_icon');
        }

        return '<i class="fa fa-' . $icon . '"></i>';
    }

    private static function _get_icon_map(string $config_key, string $fallback) : array
    {
        $config = midcom_baseclasses_components_configuration::get('midcom.helper.reflector', 'config');
        $icon_map = [];

        foreach ($config->get_array($config_key) as $icon => $classes) {
            $icon_map = array_merge($icon_map, array_fill_keys($classes, $icon));
        }
        $icon_map['__default__'] ??= $fallback;

        return $icon_map;
    }

    /**
     * Get class properties to use as search fields in choosers or other direct DB searches
     */
    public function get_search_properties() : array
    {
        // Return cached results if we have them
        static $cache = [];
        if (isset($cache[$this->mgdschema_class])) {
            return $cache[$this->mgdschema_class];
        }
        debug_add("Starting analysis for class {$this->mgdschema_class}");

        $properties = self::get_object_fieldnames(new $this->mgdschema_class);

        $default_properties = [
            'title' => true,
            'tag' => true,
            'firstname' => true,
            'lastname' => true,
            'official' => true,
            'username' => true,
        ];

        $search_properties = array_intersect_key($default_properties, array_flip($properties));

        foreach ($properties as $property) {
            if (str_contains($property, 'name')) {
                $search_properties[$property] = true;
            }
            // TODO: More per property heuristics
        }
        // TODO: parent and up heuristics

        $label_prop = $this->get_label_property();

        if (    $label_prop != 'guid'
             && $this->property_exists($label_prop)) {
            $search_properties[$label_prop] = true;
        }

        // Exceptions - always search these fields
        $always_search_all = $this->_config->get_array('always_search_fields');
        if (!empty($always_search_all[$this->mgdschema_class])) {
            $fields = array_intersect($always_search_all[$this->mgdschema_class], $properties);
            $search_properties += array_flip($fields);
        }

        // Exceptions - never search these fields
        $never_search_all = $this->_config->get_array('never_search_fields');
        if (!empty($never_search_all[$this->mgdschema_class])) {
            $search_properties = array_diff_key($search_properties, array_flip($never_search_all[$this->mgdschema_class]));
        }

        $search_properties = array_keys($search_properties);
        debug_print_r("Search properties for {$this->mgdschema_class}: ", $search_properties);
        $cache[$this->mgdschema_class] = $search_properties;
        return $search_properties;
    }

    /**
     * Gets a list of link properties and the links target info
     *
     * Link info key specification
     *     'class' string link target class name
     *     'target' string link target property (of target class)
     *
     * @return array multidimensional array keyed by property, values are arrays with link info (or false in case of failure)
     */
    public function get_link_properties() : array
    {
        // Return cached results if we have them
        static $cache = [];
        if (isset($cache[$this->mgdschema_class])) {
            return $cache[$this->mgdschema_class];
        }
        debug_add("Starting analysis for class {$this->mgdschema_class}");

        // Get property list and start checking (or abort on error)
        $links = [];
        foreach (self::get_object_fieldnames(new $this->mgdschema_class) as $property) {
            if ($property == 'guid') {
                // GUID, even though of type MGD_TYPE_GUID, is never a link
                continue;
            }

            if (   !$this->is_link($property)
                && $this->get_midgard_type($property) != MGD_TYPE_GUID) {
                continue;
            }
            debug_add("Processing property '{$property}'");
            $linkinfo = [
                'class' => $this->get_link_name($property),
                'target' => $this->get_link_target($property),
                'type' => $this->get_midgard_type($property),
            ];

            if (!$linkinfo['target'] && $linkinfo['type'] == MGD_TYPE_GUID) {
                $linkinfo['target'] = 'guid';
            }

            $links[$property] = $linkinfo;
        }

        debug_print_r("Links for {$this->mgdschema_class}: ", $links);
        $cache[$this->mgdschema_class] = $links;
        return $links;
    }

    /**
     * Map extended classes
     *
     * For example org.openpsa.* components often expand core objects,
     * in config we specify which classes we wish to substitute with which
     *
     * @return string new classname (or original in case no rewriting is to be done)
     */
    protected static function class_rewrite(string $schema_type) : string
    {
        $extends = midcom_baseclasses_components_configuration::get('midcom.helper.reflector', 'config')->get_array('class_extends');
        if (   isset($extends[$schema_type])
            && class_exists($extends[$schema_type])) {
            return $extends[$schema_type];
        }
        return $schema_type;
    }

    /**
     * See if two MgdSchema classes are the same
     *
     * NOTE: also takes into account the various extended class scenarios
     */
    public static function is_same_class(string $class1, string $class2) : bool
    {
        return self::resolve_baseclass($class1) == self::resolve_baseclass($class2);
    }

    /**
     * Get the MgdSchema classname for given class
     */
    public static function resolve_baseclass(string|object $classname) : string
    {
        static $cached = [];

        if (is_object($classname)) {
            $class_instance = $classname;
            $classname = $classname::class;
        }

        if (!$classname) {
            throw new midcom_error('Class name must not be empty');
        }

        $classname = ClassUtils::getRealClass($classname);

        if (isset($cached[$classname])) {
            return $cached[$classname];
        }

        if (!isset($class_instance)) {
            $class_instance = new $classname();
        }

        // Check for decorators first
        if (!empty($class_instance->__mgdschema_class_name__)) {
            $parent_class = $class_instance->__mgdschema_class_name__;
        } else {
            $parent_class = $classname;
        }

        $cached[$classname] = self::class_rewrite($parent_class);

        return $cached[$classname];
    }

    private function get_property(string $type, object $object) : ?string
    {
        // Cache results per class within request
        $key = $object::class;
        if (array_key_exists($key, self::$_cache[$type])) {
            return self::$_cache[$type][$key];
        }
        self::$_cache[$type][$key] = null;

        // Configured properties
        foreach ($this->_config->get_array($type . '_exceptions') as $class => $property) {
            if (midcom::get()->dbfactory->is_a($object, $class)) {
                if ($property !== false) {
                    if ($this->property_exists($property)) {
                        self::$_cache[$type][$key] = $property;
                    } else {
                        debug_add("Matched class '{$key}' to '{$class}' via is_a but property '{$property}' does not exist", MIDCOM_LOG_ERROR);
                    }
                }
                return self::$_cache[$type][$key];
            }
        }
        // The simple heuristic
        if ($this->property_exists($type)) {
            self::$_cache[$type][$key] = $type;
        }
        return self::$_cache[$type][$key];
    }

    /**
     * Resolve the "name" property of given object
     */
    public static function get_name_property(object $object) : ?string
    {
        return self::get($object)->get_property('name', $object);
    }

    /**
     * Resolve the "title" of given object
     *
     * NOTE: This is distinctly different from get_object_label, which will always return something
     * even if it's just the class name and GUID, also it will for some classes include extra info (like datetimes)
     * which we do not want here.
     */
    public static function get_object_title(object $object) : ?string
    {
        if ($title_property = self::get_title_property($object)) {
            return (string) $object->{$title_property};
        }
        // Could not resolve valid property
        return null;
    }

    /**
     * Resolve the "title" property of given object
     *
     * NOTE: This is distinctly different from get_label_property, which will always return something
     * even if it's just the guid
     */
    public static function get_title_property(object $object) : ?string
    {
        return self::get($object)->get_property('title', $object);
    }
}
