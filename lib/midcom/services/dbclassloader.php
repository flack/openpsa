<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\Common\Util\ClassUtils;

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
 *      public string $__midcom_class_name__ = __CLASS__;
 *      public string $__mgdschema_class_name__ = 'midgard_article';
 *
 * </code>
 *
 * @package midcom.services
 */
class midcom_services_dbclassloader
{
    private $map;

    public function __construct(array $map)
    {
        $this->map = $map;
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
            'org.openpsa.role' => 'org.openpsa.projects',
            'org.openpsa.hour' => 'org.openpsa.expenses'
        ];

        while (!empty($class_parts)) {
            $component = implode('.', $class_parts);
            if (array_key_exists($component, $component_map)) {
                $component = $component_map[$component];
            }

            if (array_key_exists($component, $this->map)) {
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
     * @param string|object $classname The classname (or object) to check
     */
    public function get_midcom_class_name_for_mgdschema_object($classname) : ?string
    {
        static $dba_classes_by_mgdschema = [];

        if (is_object($classname)) {
            $classname = ClassUtils::getClass($classname);
        } elseif (!is_string($classname)) {
            debug_print_r("Invalid input provided", $classname, MIDCOM_LOG_WARN);
            return null;
        }

        if (!array_key_exists($classname, $dba_classes_by_mgdschema)) {
            foreach ($this->map as $mapping) {
                if (array_key_exists($classname, $mapping)) {
                    $dba_classes_by_mgdschema[$classname] = $mapping[$classname];
                    break;
                }
            }
            if (!array_key_exists($classname, $dba_classes_by_mgdschema)) {
                debug_add("{$classname} cannot be resolved to any DBA class name");
                $dba_classes_by_mgdschema[$classname] = null;
            }
        }

        return $dba_classes_by_mgdschema[$classname];
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
                if ($this->is_midcom_db_object($classname)) {
                    $mapping[$classname] = (new $classname)->__mgdschema_class_name__;
                }
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
        return is_subclass_of($object, midcom_core_dbaobject::class)
            || is_a($object, midcom_core_dbaproxy::class, true);
    }

    public function get_component_classes(string $component) : array
    {
        return $this->map[$component];
    }
}
