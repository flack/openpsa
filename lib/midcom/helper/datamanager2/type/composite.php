<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: composite.php 26504 2010-07-06 12:19:31Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 composite object management type.
 *
 * This type allows you to control an arbitrary number of "child objects" on a given object.
 * It can only operate if the storage implementation provides it with a Midgard Object.
 * The storage location provided by the schema is unused at this time, as child object
 * operations cannot be undone.
 *
 * The type can manage an arbitrary number of objects. Each object is identified
 * by a GUID. It provides management functions for existing child objects which allow you to
 * add, delete and update them in all variants. These functions are executed immediately on the
 * storage object, no undo is possible.
 *
 * <h3>Available configuration options:</h3>
 *
 * - <b>child_class</b>: the MidCOM DBA class of the child object
 * - <b>child_schemadb</b>: Path to DM2 schema database used for child elements
 * - <b>child_foreign_key_fieldname</b>: the field of the child objects used to connect them to the parent. By default <i>up</i>.
 * - <b>parent_key_fieldname</b>: field of the parent used as identifier in child objects. Typically <i>id</i> or <i>guid</i>.
 * - Array <b>child_constraints</b>: Other query constraints for the child objects as arrays containing field, constraint type and value suitable for QB add_constraint usage.
 * - <b>style_element_name</b>: Name used for the header, footer and item elements of the object list
 * - <b>window_mode</b>: Whether the composites should be edited in a modal pop-up window instead of in-place. Useful for tight spaces.
 * - <b>maximum_items</b>: How many items are allowed into the composite. After this creation is disabled.
 * - <b>enable_creation</b>: Whether creation of new items is allowed
 * - <b>area_element</b>: The HTML element surrounding the composites. By default div.
 * - <b>context</b>: Composite context for filtering from several composite items in one schema
 * - <b>context_key</b>: Composite context key in the child object
 *
 * <h3>Usage</h3>
 *
 * <b>child_constraints</b>
 *
 * Each child constraint is an array, where the values are _fieldname_, _operator_ and _constraint_.
 *
 * 'child_constraints' => array
 * (
 *     array
 *     (
 *         'creator',          // Fieldname
 *         '=',                // Operator
 *         midcom_connection::get_user(),  // Constraint
 *     ),
 *     array
 *     (
 *         'name',      // Fieldname
 *         'LIKE',      // Operator
 *         '%index%',   // Constraint
 *     ),
 * ),
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_composite extends midcom_helper_datamanager2_type
{
    /**
     * MgdSchema class of the child item
     *
     * @var mixed MgdSchema object
     */
    public $child_class = '';

    /**
     * midcom_helper_datamanager2_schema compatible schema path of the composite item
     *
     * @var mixed MgdSchema object
     */
    public $child_schemadb = '';

    /**
     * Key of the child item for linking child to master item
     *
     * @var String
     */
    public $child_foreign_key_fieldname = 'up';

    /**
     * Key of the master item for linking child to master item
     *
     * @var String
     */
    public $parent_key_fieldname = 'id';

    /**
     * Constraints for selecting the correct child items
     *
     * @var Array
     */
    public $child_constraints = array();

    /**
     * Array of orders for sorting the items
     *
     * @var Array
     */
    public $orders = array
    (
        'metadata.created' => 'ASC',
    );

    /**
     * Context for the composite item. A must if there are more than one composite in one schema.
     *
     * @var String
     */
    public $context = null;

    /**
     * Key for storing the context information in the child item. Has to be a property of the child.
     *
     * @var String
     */
    public $context_key = 'parameter';

    var $style_element_name = 'child';
    var $window_mode = false;
    var $wide_mode = false;

    /**
     * Maximum amount of items
     *
     * @var integer
     */
    public $maximum_items = null;

    /**
     * Should the creation mode be enabled
     *
     * @var boolean
     */
    public $enable_creation = true;

    /**
     * Wrapping item tag name
     *
     * @var String
     */
    public $area_element = 'div';

    /**
     * Default values for the composite item
     *
     * @var Array
     */
    public $defaults = array();

    /**
     * The schema database in use for the child elements
     *
     * @var Array
     * @access private
     */
    var $_schemadb = null;

    /**
     * Array of Datamanager 2 controllers for child object display and management
     *
     * @var array
     * @access private
     */
    var $_controllers = Array();

    /**
     * Array of Datamanager 2 controllers for child object creation
     *
     * @var array
     * @access private
     */
    var $_creation_controllers = Array();

    /**
     * All objects covered by this field. The array contains Midgard objects indexed by
     * their identifier within the field.
     *
     * @var Array
     */
    public $objects = Array();

    /**
     * Initialize the class, if necessary, create a callback instance, otherwise
     * validate that an option array is present.
     */
    function _on_initialize()
    {
        if (!$this->child_class)
        {
            // TODO: We could have some smart defaults here
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'The configuration option child class must be defined for all composite types.');
            // This will exit.
        }

        if (!$this->child_schemadb)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'The configuration option child schema database must be defined for all composite types.');
            // This will exit.
        }

        if (! class_exists($this->child_class))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "The mapping class {$this->child_class} does not exist.");
            // This will exit.
        }

        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->child_schemadb);

        parent::_on_initialize();

        return true;
    }

    /**
     * This function loads child objects of the storage object. It
     * will leave the field empty in case the storage object is null.
     */
    function convert_from_storage ($source)
    {
        $this->objects = Array();

        if ($this->storage->object === null)
        {
            // We don't have a storage object, skip the rest of the operations.
            return;
        }

        $qb = $_MIDCOM->dbfactory->new_query_builder($this->child_class);
        $parent_key = $this->parent_key_fieldname;
        $qb->add_constraint($this->child_foreign_key_fieldname, '=', $this->storage->object->$parent_key);

        // Set the schema defined constraints
        foreach ($this->child_constraints as $constraint)
        {
            $qb->add_constraint($constraint[0], $constraint[1], $constraint[2]);
        }

        // Order according to configuration
        foreach ($this->orders as $field => $order)
        {
            $qb->add_order($field, $order);
        }

        // Context filtering
        if ($this->context)
        {
            switch ($this->context_key)
            {
                case 'parameter':
                   $qb->add_constraint('parameter.domain', '=', 'midcom.helper.datamanager2.composite');
                   $qb->add_constraint('parameter.name', '=', 'context');
                   $qb->add_constraint('parameter.value', '=', $this->context);
                   break;

                default:
                    $qb->add_constraint($this->context_key, '=', $this->context);
            }
        }

        $raw_objects = $qb->execute();
        foreach ($raw_objects as $object)
        {
            $this->objects[$object->guid] = $object;
        }

        // Load a creation controller per each schema in the database
        $this->_load_creation_controllers();
    }

    function convert_to_storage()
    {
        return '';
    }

    /**
     * DM2 creation callback for creating children.
     */
    function & create_object(&$controller)
    {
        $child_class = $this->child_class;
        $foreign_key = $this->child_foreign_key_fieldname;
        $parent_key = $this->parent_key_fieldname;

        $object = new $child_class();
        $object->$foreign_key = $this->storage->object->$parent_key;

        foreach ($this->child_constraints as $constraint)
        {
            // Handle the "=" constraints
            if ($constraint[1] == '=')
            {
                $object->$constraint[0] = $constraint[2];
            }
        }

        if (! $object->create())
        {
            debug_print_r('We operated on this object:', $object);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to create a new child object. Last Midgard error was: '. midcom_connection::get_error_string());
            // This will exit.
        }

        // Notify parent of changes
        $this->storage->object->update();

        return $object;
    }

    /**
     * Deletes an existing child object.
     *
     * @param string $identifier The identifier of the object that should be deleted.
     * @return boolean Indicating success.
     */
    function delete_object($identifier)
    {
        if (! array_key_exists($identifier, $this->objects))
        {
            debug_add("Failed to delete the object: The identifier is unknown.", MIDCOM_LOG_INFO);
            return false;
        }

        $object = $this->objects[$identifier];
        if (! $object>delete())
        {
            debug_add("Failed to delete the object: DBA delete call returned false.", MIDCOM_LOG_INFO);
            return false;
        }

        // Notify parent of changes
        $this->storage->object->update();

        unset($this->objects[$identifier]);
        return true;
    }

    /**
     * This call will unconditionally delete all objects currently contained by the type.
     *
     * @return boolean Indicating success.
     */
    function delete_all_objects()
    {
        foreach ($this->objects as $identifier => $object)
        {
            if (! $this->delete_object($identifier))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Adds an item for an existing object
     */
    function add_object_item($identifier)
    {
        $object = $this->objects[$identifier];
        $this->_controllers[$identifier] = midcom_helper_datamanager2_controller::create('ajax');

        if ($this->window_mode)
        {
            $this->_controllers[$identifier]->window_mode = $this->window_mode;
        }
        if ($this->wide_mode)
        {
            $this->_controllers[$identifier]->wide_mode = $this->wide_mode;
        }

        $this->_controllers[$identifier]->allow_removal = true;

        $this->_controllers[$identifier]->schemadb = $this->_schemadb;
        $this->_controllers[$identifier]->set_storage($object);
        switch ($this->_controllers[$identifier]->process_ajax(false))
        {
            case 'view':
                break;
            case 'ajax_delete':
                $_MIDCOM->cache->content->content_type('text/xml');
                $_MIDCOM->header('Content-type: text/xml; charset=utf-8');
                echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";
                echo "<deletion id=\"{$identifier}\">\n";
                echo '    <status>' . midcom_connection::get_error_string() . "</status>\n";
                echo "</deletion>\n";

                $_MIDCOM->finish();
                _midcom_stop_request();
            case 'ajax_saved':
                // Notify parent of changes
                $this->storage->object->update();
            default:
                $_MIDCOM->finish();
                _midcom_stop_request();
        }
    }

    function _load_creation_controllers()
    {
        if (!$this->enable_creation)
        {
            return false;
        }

        if (   !is_null($this->maximum_items)
            && count($this->objects) >= $this->maximum_items)
        {
            return false;
        }

        if ($this->storage->object->can_do('midgard:create'))
        {
            foreach (array_keys($this->_schemadb) as $name)
            {
                $this->_creation_controllers[$name] = midcom_helper_datamanager2_controller::create('create');
                $this->_creation_controllers[$name]->form_identifier = "midcom_helper_datamanager2_controller_create_{$this->name}_{$this->storage->object->guid}_{$name}";
                $this->_creation_controllers[$name]->ajax_mode = true;
                $this->_creation_controllers[$name]->ajax_options = Array();
                if ($this->window_mode)
                {
                    $this->_creation_controllers[$name]->window_mode = $this->window_mode;
                    $this->_creation_controllers[$name]->ajax_options['window_mode'] = $this->window_mode;
                }
                if ($this->wide_mode)
                {
                    $this->_creation_controllers[$name]->wide_mode = $this->wide_mode;
                    $this->_creation_controllers[$name]->ajax_options['wide_mode'] = $this->wide_mode;
                }

                $this->_creation_controllers[$name]->schemadb = $this->_schemadb;
                $this->_creation_controllers[$name]->schemaname = $name;
                $this->_creation_controllers[$name]->callback_object = $this;
                $this->_creation_controllers[$name]->callback_method = 'create_object';
                $this->_creation_controllers[$name]->defaults = $this->defaults;
                if (! $this->_creation_controllers[$name]->initialize())
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
                    // This will exit.
                }
            }
        }
    }

    function add_creation_data()
    {
        if (!$this->enable_creation)
        {
            return false;
        }

        if (   !is_null($this->maximum_items)
            && count($this->objects) >= $this->maximum_items)
        {
            return false;
        }

        if (!$this->storage->object->can_do('midgard:create'))
        {
            return false;
        }

        foreach (array_keys($this->_schemadb) as $name)
        {
            // Add default values to fields
            $item_html = array();
            $form_identifier = $this->_creation_controllers[$name]->form_identifier;
            foreach ($this->_schemadb[$name]->fields as $fieldname => $definition)
            {
                $item_html[$fieldname] = "<span class=\"{$form_identifier}\" id=\"{$form_identifier}_{$fieldname}\">&lt;{$fieldname}&gt;</span>";
            }
            $request_data = array
            (
                'item_html'  => $item_html,
                'item'       => null,
                'item_count' => null,
                'item_total' => null,
            );
            $_MIDCOM->set_custom_context_data('midcom_helper_datamanager2_widget_composite', $request_data);
            echo "<{$this->area_element} id=\"{$form_identifier}_area\" class=\"temporary_item\" style=\"display: none;\">\n";
            $_MIDCOM->style->show("_dm2_composite_{$this->style_element_name}_item");
            echo "</{$this->area_element}>\n";
        }

        $_MIDCOM->style->show("_dm2_composite_{$this->style_element_name}_footer");

        foreach (array_keys($this->_schemadb) as $name)
        {
            $form_identifier = $this->_creation_controllers[$name]->form_identifier;
            echo "<button name=\"create_{$name}\" id=\"{$form_identifier}_button\" class=\"midcom_helper_datamanager2_composite_create_button\">\n";
            echo sprintf($this->_l10n_midcom->get('create %s'), $this->_schemadb[$name]->_l10n_schema->get($this->_schemadb[$name]->description));
            echo "</button>\n";
        }

        return true;
    }

    function convert_from_csv ($source)
    {
        // TODO: Not yet supported
        return '';
    }

    function convert_to_csv()
    {
        // TODO: Not yet supported
        return '';
    }


    /**
     * Displays the child objects
     */
    function convert_to_html()
    {
        ob_start();

        $item_total = count($this->objects);
        $request_data = array
        (
            'item_total' => $item_total,
        );

        $_MIDCOM->set_custom_context_data('midcom_helper_datamanager2_widget_composite', $request_data);
        $_MIDCOM->style->show("_dm2_composite_{$this->style_element_name}_header");

        $item_count = 0;
        foreach ($this->objects as $identifier => $object)
        {
            $item_count++;
            if (!array_key_exists($identifier, $this->_controllers))
            {
                $this->add_object_item($identifier);
            }
            $request_data['item_html'] = $this->_controllers[$identifier]->get_content_html();
            $request_data['item'] = $object;
            $request_data['item_count'] = $item_count;

            $_MIDCOM->set_custom_context_data('midcom_helper_datamanager2_widget_composite', $request_data);
            echo "<{$this->area_element} id=\"{$this->_controllers[$identifier]->form_identifier}_area\">\n";
            $_MIDCOM->style->show("_dm2_composite_{$this->style_element_name}_item");
            echo "</{$this->area_element}>\n";
        }

        //If creation data was added, the footer is already spliced in
        if (!$this->add_creation_data())
        {
            $_MIDCOM->style->show("_dm2_composite_{$this->style_element_name}_footer");
        }

        $results = ob_get_contents();
        ob_end_clean();
        return $results;
    }
}
?>