<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\mgdobject;

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
 * [
 *     'midgard_article' => 'midcom_db_article'
 * ]
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
     * Simple helper to check whether we are dealing with a MgdSchema or MidCOM DBA
     * object or a subclass thereof.
     *
     * @param object $object The object to check
     */
    public function is_mgdschema_object($object) : bool
    {
        // Sometimes we might get class string instead of an object
        if (is_string($object)) {
            $object = new $object;
        }
        if ($this->is_midcom_db_object($object)) {
            return true;
        }

        return is_a($object, mgdobject::class);
    }

    /**
     * Get component name associated with a class name to get its DBA classes defined
     */
    public function get_component_for_class(string $classname) : ?string
    {
        $class_parts = array_filter(explode('_', $classname));
        // Fix for incorrectly named classes
        $component_map = [
            'midgard' => 'midcom',
            'org.openpsa.campaign' => 'org.openpsa.directmarketing',
            'org.openpsa.link' => 'org.openpsa.directmarketing',
            'org.openpsa.document' => 'org.openpsa.documents',
            'org.openpsa.organization' => 'org.openpsa.contacts',
            'org.openpsa.person' => 'org.openpsa.contacts',
            'org.openpsa.role' => 'org.openpsa.contacts',
            'org.openpsa.member' => 'org.openpsa.contacts',
            'org.openpsa.salesproject' => 'org.openpsa.sales',
            'org.openpsa.offer' => 'org.openpsa.sales',
            'org.openpsa.event' => 'org.openpsa.calendar',
            'org.openpsa.eventmember' => 'org.openpsa.calendar',
            'org.openpsa.invoice' => 'org.openpsa.invoices',
            'org.openpsa.billing' => 'org.openpsa.invoices',
            'org.openpsa.query' => 'org.openpsa.reports',
            'org.openpsa.task' => 'org.openpsa.projects',
            'org.openpsa.project' => 'org.openpsa.projects',
            'org.openpsa.hour' => 'org.openpsa.expenses'
        ];

        while (!empty($class_parts)) {
            $component = implode('.', $class_parts);
            if (array_key_exists($component, $component_map)) {
                $component = $component_map[$component];
            }

            if (midcom::get()->componentloader->is_installed($component)) {
                return $component;
            }
            array_pop($class_parts);
        }

        return null;
    }

    /**
     * Get a MidCOM DB class name for a MgdSchema Object.
     * We also ensure that the corresponding component has been loaded.
     *
     * @param string|object $object The object (or classname) to check
     * @return string The corresponding MidCOM DB class name, false otherwise.
     */
    public function get_midcom_class_name_for_mgdschema_object($object)
    {
        static $dba_classes_by_mgdschema = [];

        if (is_string($object)) {
            // In some cases we get a class name instead
            $classname = $object;
        } elseif (is_object($object)) {
            $classname = get_class($object);
        } else {
            debug_print_r("Invalid input provided", $object, MIDCOM_LOG_WARN);
            return false;
        }

        if (isset($dba_classes_by_mgdschema[$classname])) {
            return $dba_classes_by_mgdschema[$classname];
        }

        if (!$this->is_mgdschema_object($object)) {
            debug_add("{$classname} is not an MgdSchema object, not resolving to MidCOM DBA class", MIDCOM_LOG_WARN);
            $dba_classes_by_mgdschema[$classname] = false;
            return false;
        }

        if ($classname == midcom::get()->config->get('person_class')) {
            $component = 'midcom';
        } else {
            $component = $this->get_component_for_class($classname);
            if (!$component) {
                debug_add("Component for class {$classname} cannot be found", MIDCOM_LOG_WARN);
                $dba_classes_by_mgdschema[$classname] = false;
                return false;
            }
        }
        $definitions = $this->get_component_classes($component);

        //TODO: This allows components to override midcom classes fx. Do we want that?
        $dba_classes_by_mgdschema = array_merge($dba_classes_by_mgdschema, $definitions);

        if (array_key_exists($classname, $dba_classes_by_mgdschema)) {
            return $dba_classes_by_mgdschema[$classname];
        }

        debug_add("{$classname} cannot be resolved to any DBA class name");
        $dba_classes_by_mgdschema[$classname] = false;
        return false;
    }

    /**
     * Get an MgdSchema class name for a MidCOM DBA class name
     *
     * @return string The corresponding MidCOM DBA class name, false otherwise.
     */
    public function get_mgdschema_class_name_for_midcom_class(string $classname)
    {
        static $mapping = [];

        if (!array_key_exists($classname, $mapping)) {
            $mapping[$classname] = false;

            if (class_exists($classname)) {
                $dummy_object = new $classname();
                if (!$this->is_midcom_db_object($dummy_object)) {
                    return false;
                }
                $mapping[$classname] = $dummy_object->__mgdschema_class_name__;
            }
        }

        return $mapping[$classname];
    }

    /**
     * Simple helper to check whether we are dealing with a MidCOM Database object
     * or a subclass thereof.
     *
     * @param object|string $object The object (or classname) to check
     */
    public function is_midcom_db_object($object) : bool
    {
        if (is_object($object)) {
            return ($object instanceof midcom_core_dbaobject || $object instanceof midcom_core_dbaproxy);
        }
        if (is_string($object) && class_exists($object)) {
            return $this->is_midcom_db_object(new $object);
        }

        return false;
    }

    public function get_component_classes(string $component) : array
    {
        $map = midcom::get()->componentloader->get_manifest($component)->class_mapping;
        if ($component == 'midcom') {
            $map[midcom::get()->config->get('person_class')] = midcom_db_person::class;
        }
        return $map;
    }
}
