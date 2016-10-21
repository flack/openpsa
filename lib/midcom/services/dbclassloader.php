<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * <b>How to write database class definitions:</b>
 *
 * The general idea is to provide MidCOM with a way to hook into every database interaction
 * between the component and the Midgard core.
 *
 * Since PHP does not allow for multiple inheritance (which would be really useful here),
 * a decorator pattern is used, which connects the class you actually use in your component
 * and the original MgdSchema class while at the same time routing all function calls through
 * midcom_core_dbaobject.
 *
 * The class loader does not require much information when registering classes:
 * An example declaration looks like this:
 *
 * <code>
 * Array
 * (
 *     'midgard_article' => 'midcom_db_article'
 * )
 * </code>
 *
 * The key is the MgdSchema class name from that you want to use. The class specified must exist.
 *
 * The value is the name of the MidCOM base class you intend to create.
 * It is checked for basic validity against the PHP restrictions on symbol naming, but the
 * class itself is not checked for existence. You <i>must</i> declare the class as listed at
 * all times, as typecasting and detection is done using this metadata property in the core.
 *
 * <b>Inherited class requirements</b>
 *
 * The classes you inherit from the intermediate stub classes must at this time satisfy one
 * requirement: You have to declare the midcom and mgdschema classnames:
 *
 * <code>
 * class midcom_db_article
 *     extends midcom_core_dbaobject
 * {
 *      public $__midcom_class_name__ = __CLASS__;
 *      public $__mgdschema_class_name__ = 'midgard_article';
 *
 * </code>
 *
 * @package midcom.services
 */
class midcom_services_dbclassloader
{
    /**
     * List of all midgard classes which have been loaded.
     *
     * This list only contains the class definitions that have been used to
     * construct  the actual helper classes.
     *
     * @var Array
     */
    private $_midgard_classes = Array();

    /**
     * A mapping storing which component handles which class.
     *
     * This is used to ensure that all MidCOM DBA main classes are loaded when
     * casting  MgdSchema objects to DBA objects. Especially important for the
     * generic by-GUID object getter.
     *
     * @var Array
     */
    private $_mgdschema_class_handler = Array();

    /**
     * This is the main class loader function. It takes a component/filename pair as
     * arguments, the first specifying the place to look for the latter.
     *
     * For example, if you call load_classes('net.nehmer.static', 'my_classes.inc'), it will
     * look in the directory MIDCOM_ROOT/net/nehmer/static/config/my_classes.inc. The magic
     * component 'midcom' goes for the MIDCOM_ROOT/midcom/config directory and is reserved
     * for MidCOM core classes and compatibility classes.
     */
    function load_classes($component, $filename, $definition_list = null)
    {
        if (is_null($definition_list))
        {
            $definition_list = $this->_read_class_definition_file($component, $filename);;
        }

        $this->_register_loaded_classes($definition_list, $component);
    }

    /**
     * Validate a class definition list for correctness.
     *
     * Where possible, missing elements are completed with sensible defaults.
     *
     * @param array $definition_list A reference to the definition list to verify.
     */
    function _validate_class_definition_list(array $definition_list)
    {
        foreach ($definition_list as $mgdschema_class => $midcom_class)
        {
            if (! class_exists($mgdschema_class))
            {
                throw new midcom_error("Validation failed: Key {$midcom_class} had an invalid mgdschema_class_name element: {$mgdschema_class}. Probably the required MgdSchema is not loaded.");
            }

            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $midcom_class) == 0)
            {
                throw new midcom_error("Validation failed: Key {$mgdschema_class} had an invalid mgdschema_class_name element.");
            }
        }
    }

    /**
     * Loads a class definition file from the disk and returns its contents.
     *
     * It will translate component and filename into a full path and delivers
     * the contents as an array.
     *
     * @param string $component The name of the component for which the class file has to be loaded. The path must
     *     resolve with the component loader unless you use 'midcom' to load MidCOM core class definition files.
     * @return array The contents of the file.
     */
    private function _read_class_definition_file($component, $filename)
    {
        if ($component == 'midcom')
        {
            $filename = MIDCOM_ROOT . "/midcom/config/{$filename}";
        }
        else
        {
            $filename = midcom::get()->componentloader->path_to_snippetpath($component) . "/config/{$filename}";
        }
        if (! file_exists($filename))
        {
            throw new midcom_error("Failed to access the file {$filename}: File does not exist.");
        }

        $contents = file_get_contents($filename);
        return midcom_helper_misc::parse_config($contents);
    }

    /**
     * Simple helper that adds a list of classes to the loaded classes listing.
     *
     * This creates a mapping of which class is handled by which component.
     * The generic by-GUID loader and the class conversion tools in the dbfactory
     * require this information to be able to load the required components on-demand.
     *
     * @param array $definitions The list of classes which have been loaded along with the meta information.
     * @param string $component The component name of the classes to add
     */
    private function _register_loaded_classes(array $definitions, $component)
    {
        $this->_validate_class_definition_list($definitions);

        foreach ($definitions as $mgdschema_class => $midcom_class)
        {
            $this->_mgdschema_class_handler[$midcom_class] = $component;

            if (   substr($mgdschema_class, 0, 8) == 'midgard_'
                || substr($mgdschema_class, 0, 12) == 'midcom_core_'
                || $mgdschema_class == midcom::get()->config->get('person_class'))
            {
                $this->_midgard_classes[$mgdschema_class] = $midcom_class;
            }
        }
    }

    /**
     * Simple helper to check whether we are dealing with a MgdSchema or MidCOM DBA
     * object or a subclass thereof.
     *
     * @param object $object The object to check
     * @return boolean true if this is a MgdSchema object, false otherwise.
     */
    public function is_mgdschema_object($object)
    {
        // Sometimes we might get class string instead of an object
        if (is_string($object))
        {
            $object = new $object;
        }
        if ($this->is_midcom_db_object($object))
        {
            return true;
        }

        if (!extension_loaded('midgard'))
        {
            return is_a($object, 'midgard_object');
        }

        // Midgard1 compat, the quick way
        if (in_array(get_class($object), midcom_connection::get_schema_types()))
        {
            return true;
        }

        // Then, do a thorough scan
        foreach (midcom_connection::get_schema_types() as $mgdschema_class)
        {
            if (is_a($object, $mgdschema_class))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Get component name associated with a class name to get its DBA classes defined
     *
     * @param string $classname Class name to load a component for
     * @return string component name if found for the class, false otherwise
     */
    public function get_component_for_class($classname)
    {
        $class_parts = array_filter(explode('_', $classname));
        $component = '';
        // Fix for incorrectly named classes
        $component_map = array
        (
            'midcom.db' => 'midcom',
            'midcom.core' => 'midcom',
            'midgard' => 'midcom',
            'org.openpsa.campaign' => 'org.openpsa.directmarketing',
            'org.openpsa.link' => 'org.openpsa.directmarketing',
            'org.openpsa.document' => 'org.openpsa.documents',
            'org.openpsa.organization' => 'org.openpsa.contacts',
            'org.openpsa.person' => 'org.openpsa.contacts',
            'org.openpsa.role' => 'org.openpsa.contacts',
            'org.openpsa.member' => 'org.openpsa.contacts',
            'org.openpsa.salesproject' => 'org.openpsa.sales',
            'org.openpsa.event' => 'org.openpsa.calendar',
            'org.openpsa.eventmember' => 'org.openpsa.calendar',
            'org.openpsa.invoice' => 'org.openpsa.invoices',
            'org.openpsa.billing' => 'org.openpsa.invoices',
            'org.openpsa.query' => 'org.openpsa.reports',
            'org.openpsa.task' => 'org.openpsa.projects',
            'org.openpsa.project' => 'org.openpsa.projects',
            'org.openpsa.hour' => 'org.openpsa.projects'
        );

        foreach ($class_parts as $part)
        {
            if (empty($component))
            {
                $component = $part;
            }
            else
            {
                $component .= ".{$part}";
            }
            if (array_key_exists($component, $component_map))
            {
                $component = $component_map[$component];
            }

            if (midcom::get()->componentloader->is_installed($component))
            {
                return $component;
            }
        }
        return false;
    }

    /**
     * Load a component associated with a class name to get its DBA classes defined
     *
     * @param string $classname Class name to load a component for
     * @return boolean true if a component was found for the class, false otherwise
     */
    public function load_component_for_class($classname)
    {
        $component = $this->get_component_for_class($classname);
        if (!$component)
        {
            return false;
        }

        if (midcom::get()->componentloader->is_loaded($component))
        {
            return true;
        }

        return midcom::get()->componentloader->load_graceful($component);
    }

    /**
     * Get a MidCOM DB class name for a MgdSchema Object.
     *
     * @param string|object $object The object (or classname) to check
     * @return string The corresponding MidCOM DB class name, false otherwise.
     */
    public function get_midcom_class_name_for_mgdschema_object($object)
    {
        static $dba_classes_by_mgdschema = array();

        if (is_string($object))
        {
            // In some cases we get a class name instead
            $classname = $object;
        }
        else if (is_object($object))
        {
            $classname = get_class($object);
        }
        else
        {
            debug_print_r("Invalid input provided", $object, MIDCOM_LOG_WARN);
            return false;
        }

        if (isset($dba_classes_by_mgdschema[$classname]))
        {
            return $dba_classes_by_mgdschema[$classname];
        }

        if (!$this->is_mgdschema_object($object))
        {
            debug_add("{$classname} is not an MgdSchema object, not resolving to MidCOM DBA class", MIDCOM_LOG_WARN);
            $dba_classes_by_mgdschema[$classname] = false;
            return false;
        }

        if ($classname == midcom::get()->config->get('person_class'))
        {
            $definitions = $this->get_midgard_classes();
        }
        else
        {
            $component = $this->get_component_for_class($classname);
            if (!$component)
            {
                debug_add("Component for class {$classname} cannot be found", MIDCOM_LOG_WARN);
                $dba_classes_by_mgdschema[$classname] = false;
                return false;
            }
            $definitions = $this->get_component_classes($component);
        }

        //TODO: This allows components to override midcom classes fx. Do we want that?
        $dba_classes_by_mgdschema = array_merge($dba_classes_by_mgdschema, $definitions);

        if (array_key_exists($classname, $dba_classes_by_mgdschema))
        {
            return $dba_classes_by_mgdschema[$classname];
        }

        debug_add("{$classname} cannot be resolved to any DBA class name");
        $dba_classes_by_mgdschema[$classname] = false;
        return false;
    }

    /**
     * Get an MgdSchema class name for a MidCOM DBA class name
     *
     * @param string $classname The MidCOM DBA classname to check
     * @return string The corresponding MidCOM DBA class name, false otherwise.
     */
    public function get_mgdschema_class_name_for_midcom_class($classname)
    {
        static $mapping = array();

        if (!array_key_exists($classname, $mapping))
        {
            $mapping[$classname] = false;

            if (class_exists($classname))
            {
                $dummy_object = new $classname();
                if (!$this->is_midcom_db_object($dummy_object))
                {
                    return false;
                }
                $mapping[$classname] = $dummy_object->__mgdschema_class_name__;
            }
        }

        return $mapping[$classname];
    }

    /**
     * This function is required by the DBA interface layer and should normally not be used
     * outside of it.
     *
     * Its purpose is to ensure that the component providing a certain DBA class instance is
     * actually loaded. This is necessary, as the class descriptions are loaded during system
     * startup now, but the full-blown DBA class is not available at that point (for performance
     * reasons). It will load the components in question when requested by any operation in the
     * system that might have to convert to a yet unloaded class, mainly this covers the type
     * conversion of arbitrary objects retrieved by the GUID object getter.
     *
     * @param string $classname The name of the MidCOM DBA class that must be available.
     * @return boolean Indicating success. False is returned only if you are requesting unknown
     *        classes and the like. Component loading failure will result in an HTTP 500, as
     *     always.
     */
    public function load_mgdschema_class_handler($classname)
    {
        if (!is_string($classname))
        {
            debug_add("Requested to load the classhandler for class name which is not a string.", MIDCOM_LOG_ERROR);
            return false;
        }

        if (! array_key_exists($classname, $this->_mgdschema_class_handler))
        {
            $component = $this->get_component_for_class($classname);
            midcom::get()->componentloader->load($component);
            if (! array_key_exists($classname, $this->_mgdschema_class_handler))
            {
                debug_add("Requested to load the classhandler for {$classname} which is not known.", MIDCOM_LOG_ERROR);
                return false;
            }
        }
        $component = $this->_mgdschema_class_handler[$classname];

        if ($component == 'midcom')
        {
            // This is always loaded.
            return true;
        }

        if (midcom::get()->componentloader->is_loaded($component))
        {
            // Already loaded, so we're fine too.
            return true;
        }

        // This throws midcom_error on any problems.
        midcom::get()->componentloader->load($component);

        return true;
    }

    /**
     * Simple helper to check whether we are dealing with a MidCOM Database object
     * or a subclass thereof.
     *
     * @param object|string $object The object (or classname) to check
     * @return boolean true if this is a MidCOM Database object, false otherwise.
     */
    public function is_midcom_db_object($object)
    {
        if (is_object($object))
        {
            return (is_a($object, 'midcom_core_dbaobject') || is_a($object, 'midcom_core_dbaproxy'));
        }
        else if (   is_string($object)
                 && class_exists($object))
        {
            return $this->is_midcom_db_object(new $object);
        }

        return false;
    }

    public function get_component_classes($component)
    {
        if ($component == 'midcom')
        {
            return $this->get_midgard_classes();
        }

        return midcom::get()->componentloader->manifests[$component]->class_mapping;
    }

    public function get_midgard_classes()
    {
        return $this->_midgard_classes;
    }
}
