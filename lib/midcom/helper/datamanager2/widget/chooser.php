<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: chooser.php 26555 2010-07-19 13:01:41Z gudd $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

if (! class_exists('midcom_helper_reflector'))
{
    $_MIDCOM->load_library('midcom.helper.reflector');
}

/**
 * Datamanager 2 Chooser widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * It can only be bound to a select type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * <b>Available configuration options:</b>
 *
 *
 * Example: (The simplest ones)
 * <code>
 * 'contacts' => Array
 * (
 *     'title' => 'contacts',
 *     'storage' => null,
 *     'type' => 'select',
 *     'type_config' => array
 *     (
 *          'require_corresponding_option' => false,
 *          'allow_multiple' => true,
 *          'multiple_storagemode' => 'array',
 *     ),
 *     'widget' => 'chooser',
 *     'widget_config' => array
 *     (
 *         'clever_class' => 'contact',
 *     ),
 * ),
 * </code>
 * OR
 * <code>
 *  'buddies' => Array
 *  (
 *      'title' => 'buddies',
 *      'storage' => null,
 *      'type' => 'select',
 *      'type_config' => array
 *      (
 *           'require_corresponding_option' => false,
 *           'allow_multiple' => true,
 *           'multiple_storagemode' => 'array',
 *      ),
 *      'widget' => 'chooser',
 *      'widget_config' => array
 *      (
 *          'clever_class' => 'buddy',
 *      ),
 *  ),
 * </code>
 * OR
 * <code>
 *  'styles' => Array
 *  (
 *      'title' => 'styles',
 *      'storage' => null,
 *      'type' => 'select',
 *      'type_config' => array
 *      (
 *           'require_corresponding_option' => false,
 *           'allow_multiple' => true,
 *           'multiple_storagemode' => 'array',
 *      ),
 *      'widget' => 'chooser',
 *      'widget_config' => array
 *      (
 *          'clever_class' => 'style',
 *      ),
 *  ),
 * </code>
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_chooser extends midcom_helper_datamanager2_widget
{
    /**
     * id of the input element
     *
     * @var String
     * @access private
     */
    var $_element_id = "chooser_widget";

    /**
     * Array of options that are passed to javascript widget
     *
     * @var Array
     * @access private
     */
    var $_js_widget_options = array();

    var $_input_element = null;

    /**
     * Class to search for
     *
     * @var string
     */
    var $class = null;

    /**
     * Which component the searched class belongs to
     *
     * @var string
     */
    var $component = null;

    /**
     * Clever class
     *
     * @var string
     */
    var $clever_class = null;

    /**
     * Associative array of constraints (besides the search term), always AND
     *
     * Example:
     * <code>
     *     'constraints' => array
     *     (
     *         array
     *         (
     *             'field' => 'username',
     *             'op' => '<>',
     *             'value' => '',
     *         ),
     *     ),
     * </code>
     *
     * @var array
     */
    var $constraints = array();

    /**
     * Fields/properties to show on results
     *
     * Example:
     * <code>
     *      'result_headers' => array
     *      (
     *          array
     *          (
     *              'name' => 'firstname',
     *              'title' => 'first name',
     *          ),
     *          array
     *          (
     *              'name' => 'lastname',
     *              'title' => 'last name',
     *          ),
     *      ),
     * </code>
     *
     * @var array
     */
    var $result_headers = array();

    /**
     * In search results replaces given field with full path to the object
     *
     * Example: (in topics)
     * <code>
     *      'generate_path_for' => 'extra',
     * </code>
     *
     * @var array
     */
    var $generate_path_for = null;

    /**
     * Fields/properties to search the keyword for, always OR and specified after the constraints above
     *
     * Example:
     * <code>
     *      'searchfields' => array
     *      (
     *          'firstname',
     *          'lastname',
     *          'email',
     *          'username'
     *      ),
     * </code>
     *
     * @var array
     */
    var $searchfields = array();

    /**
     * associative array of ordering info, always added last
     *
     * Example:
     * <code>
     *     'orders' => array
     *     (
     *         array
     *         (
     *             'lastname' => 'ASC',
     *         ),
     *         array
     *         (
     *             'firstname' => 'ASC',
     *         )
     *     ),
     * </code>
     *
     * @var array
     */
    var $orders = array();

    /**
     * Field/property to use as the key/id
     *
     * @var string
     */
    var $id_field = 'guid';

    /**
     * These options are always visible
     *
     * @var array
     */
    var $static_options = array();

    /**
     * Minimum characters necessary to trigger a search
     *
     * @var integer
     */
    var $min_chars = 2;

    /**
     * Whether to automatically append/prepend wildcards to the query
     *
     * Valid values: 'both', 'start', 'end' and <empty> (0, '', false & null)
     *
     * Example:
     * <code>
     *     'auto_wildcards' => 'both',
     * </code>
     *
     * @var string
     */
    var $auto_wildcards = 'end';

    /**
     * The javascript to append to the page
     *
     * @var string
     */
    var $_jscript = '';

    /**
     * In case the options are returned by a callback, this member holds the class.
     *
     * @var class
     */
    var $_callback = false;

    /**
     * In case the options are returned by a callback, this member holds the name of the
     * class.
     *
     * @var string
     * @access public
     */
    var $_callback_class = null;

    /**
     * The arguments to pass to the option callback constructor.
     *
     * @var mixed
     * @access public
     */
    var $_callback_args = null;

    /**
     * Renderer
     *
     * @var mixed
     */
    var $renderer = null;

    /**
     * Renderer callback
     *
     * @var class
     */
    var $_renderer_callback = false;

    /**
     * Renderer callback class name
     *
     * @var string
     */
    var $_renderer_callback_class = null;

    /**
     * Renderer callback arguments
     *
     * @var array
     */
    var $_renderer_callback_args = array();

    /**
     * The group of widgets items as QuickForm elements
     *
     * @var array
     */
    var $widget_elements = array();

    var $_static_items_html = "";
    var $_added_static_items = array();

    var $allow_multiple = true;
    var $require_corresponding_option = true;

    var $reflector_key = null;

    var $creation_mode_enabled = null;
    var $creation_handler = null;
    var $creation_default_key = null;

    var $js_format_items = array();

    /**
     * Should the sorting be enabled
     *
     * @var boolean    True if the sorting should be enabled
     */
    var $sortable = null;

    /**
     * Default search terms to provide with the chooser
     *
     * @var string Default search terms
     */
    var $default_search = null;

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (   !is_a($this->_type, 'midcom_helper_datamanager2_type_select')
            && !is_a($this->_type, 'midcom_helper_datamanager2_type_mnrelation'))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the chooser widget with it.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        // Determine the sortability from the type configuration
        if (   is_null($this->sortable)
            && isset($this->_type->sortable))
        {
            $this->sortable = $this->_type->sortable;
        }

        $this->_callback_class = $this->_type->option_callback;
        $this->_callback_args = $this->_type->option_callback_arg;

        $this->allow_multiple = $this->_type->allow_multiple;
        $this->require_corresponding_option = $this->_type->require_corresponding_option;

        if (   empty($this->class)
            && empty($this->component)
            && empty($this->clever_class))
        {
            if (   !isset($this->_callback_class)
                || empty($this->_callback_class))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Warning, the field {$this->name} does not have proper class definitions set.",
                    MIDCOM_LOG_WARN);
                debug_pop();

                return false;
            }
        }

        if (   !empty($this->renderer)
            && !$this->_check_renderer())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, the field {$this->name} renderer wasn't found or not set properly, thus widget can never show results.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (!$this->_check_class())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, cannot load class {$this->class} for field {$this->name}.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        if (!$this->id_field)
        {
            $this->id_field = 'guid';
        }

        // Check that the id_field is replication-safe
        if (   $this->id_field == 'id'
            && !is_a($this->_type, 'midcom_helper_datamanager2_type_mnrelation'))
        {
            switch ($this->_field['storage']['location'])
            {
                case 'parameter':
                case 'configuration':
                    // Storing IDs to parameters is not replication safe
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Field \"{$this->name}\" is set to store to a parameter but links via ID which is not replication-safe, aborting.", MIDCOM_LOG_WARN);
                    debug_pop();

                    $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('field %s is set to store to a parameter but links via ID which is not replication-safe, aborting'), $this->name), 'error');

                    return false;
            }

            // Normal field, verify that it is a link
            if (   $this->_type->storage->object !== null
                && !is_a($this->_type->storage->object, 'midcom_core_temporary_object'))

            {
                // We have an object, check the link type
                // Note: in creation mode we do not have this so we have no way to check)
                $mgdschema_object = $_MIDCOM->dbfactory->convert_midcom_to_midgard($this->_type->storage->object);
                if (    $mgdschema_object !== null
                     && $this->_field['storage']['location'] !== null)
                {
                    $mrp = new midgard_reflection_property(get_class($mgdschema_object));

                    if (   !$mrp
                        || !$mrp->is_link($this->_field['storage']['location']))
                    {
                        // Storing IDs to non-linked fields is not replication safe
                        debug_push_class(__CLASS__, __FUNCTION__);
                        debug_add("Field \"{$this->name}\" is set to store to property \"{$this->_field['storage']['location']}\" which is not link, making it replication-unsafe, aborting.", MIDCOM_LOG_WARN);
                        debug_pop();

                        $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('field %s is set to store to property %s but links via ID which is not replication-safe, aborting'), $this->name, $this->_field['storage']['location']), 'error');

                        return false;
                    }
                }
            }
        }

        if (   empty($this->searchfields)
            && !isset($this->_callback_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, the field {$this->name} does not have searchfields defined, it can never return results.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/chooser/jquery.chooser_widget.css'
            )
        );

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/ui.core.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/minified/ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/minified/ui.mouse.min.js');

        if ($this->sortable)
        {
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/minified/ui.draggable.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/minified/ui.droppable.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/minified/ui.sortable.min.js');
        }

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/chooser/jquery.chooser_widget.js');

        $this->_element_id = "{$this->_namespace}{$this->name}_chooser_widget";

        if (! is_null($this->creation_handler))
        {
            $this->_enable_creation_mode();
        }

        $this->_init_widget_options();

        return true;
    }

    function _enable_creation_mode()
    {
        if (! empty($this->creation_handler))
        {
            $this->creation_mode_enabled = true;
        }

        if ($this->creation_mode_enabled)
        {
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/chooser/jquery.jqModal.js');

            $script = "
                jQuery('#{$this->_element_id}_creation_dialog').jqm({
                    modal: false,
                    overlay: 40,
                    overlayClass: 'chooser_widget_creation_overlay'
                });
            ";

            //$_MIDCOM->add_jquery_state_script($script);
        }
    }

    function _check_renderer()
    {
        if (!isset($this->renderer['class']))
        {
            return false;
        }

        $this->_renderer_callback_class = $this->renderer['class'];
        $this->_renderer_callback_args = array();
        if (isset($this->renderer['args'])
            && !empty($this->renderer['args']))
        {
            $this->_renderer_callback_args = $this->renderer['args'];
        }

        if (! class_exists($this->_renderer_callback_class))
        {
            // Try auto-load.
            $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $this->_renderer_callback_class) . '.php';
            if (! file_exists($path))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Auto-loading of the renderer callback class {$this->_renderer_callback_class} from {$path} failed: File does not exist.", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
            require_once($path);
        }

        if (! class_exists($this->_renderer_callback_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The renderer callback class {$this->_renderer_callback_class} was defined as option for the field {$this->name} but did not exist.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $this->_renderer_callback = new $this->_renderer_callback_class($this->_renderer_callback_args);

        return $this->_renderer_callback->initialize();
    }

    function _check_class()
    {
        if (!empty($this->clever_class))
        {
            return $this->_check_clever_class();
        }

        if (!empty($this->_callback_class))
        {
            return $this->_check_callback();
        }

        if (class_exists($this->class))
        {
            return true;
        }

        if (! empty($this->component))
        {
            $_MIDCOM->componentloader->load($this->component);
        }

        return class_exists($this->class);
    }

    function _check_callback()
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        // debug_add("Checking callback class {$this->_callback_class}");

        if (! class_exists($this->_callback_class))
        {
            // Try auto-load.
            $path = MIDCOM_ROOT . '/' . str_replace('_', '/', $this->_callback_class) . '.php';
            if (! file_exists($path))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Auto-loading of the callback class {$this->_callback_class} from {$path} failed: File does not exist.", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
            require_once($path);
        }

        if (! class_exists($this->_callback_class))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The callback class {$this->_callback_class} was defined as option for the field {$this->name} but did not exist.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $this->_callback = new $this->_callback_class($this->_callback_args);

        // debug_pop();

        if (is_callable(array($this->_callback, 'initialize')))
        {
            return $this->_callback->initialize();
        }

        return $this->_callback;
    }

    function _check_clever_class()
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        if (   isset($_MIDCOM->auth->user)
            && method_exists($_MIDCOM->auth->user, 'get_storage'))
        {
            $current_user = $_MIDCOM->auth->user->get_storage();
        }
        else
        {
            $current_user = new midcom_db_person();
        }

        $clever_classes = array
        (
            'buddy' => array
            (
                'class' => 'net_nehmer_buddylist_entry',
                'component' => 'net.nehmer.buddylist',
                'headers' => array
                (
                    'firstname',
                    'lastname',
                    'email',
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'account',
                        'op'    => '=',
                        'value' => $current_user->guid,
                    ),
                    array
                    (
                        'field' => 'blacklisted',
                        'op'    => '=',
                        'value' => false,
                    ),
                ),
                'searchfields' => array
                (
                    'buddy.firstname',
                    'buddy.lastname',
                    'buddy.username',
                ),
                'orders' => array
                (
                    array('buddy.lastname' => 'ASC'),
                    array('buddy.firstname' => 'ASC'),
                ),
                'reflector_key' => 'buddy',
            ),
            'contact' => array
            (
                'class' => 'org_openpsa_contacts_person_dba',
                'component' => 'org.openpsa.contacts',
                'headers' => array
                (
                    'name',
                    'email',
                ),
                'constraints' => array
                (
                    array
                    (
                        'field' => 'username',
                        'op'    => '<>',
                        'value' => '',
                    ),
                ),
                'searchfields' => array
                (
                    'firstname',
                    'lastname',
                    'username',
                ),
                'orders' => array
                (
                    array('lastname' => 'ASC'),
                    array('firstname' => 'ASC'),
                ),
            ),
            'wikipage' => array
            (
                'class' => 'net_nemein_wiki_wikipage',
                'component' => 'net.nemein.wiki',
                'headers' => array
                (
                    'revised',
                    'title',
                ),
                'constraints' => array(),
                'searchfields' => array
                (
                    'title',
                ),
                'orders' => array
                (
                    array('title' => 'ASC'),
                    array('metadata.published' => 'ASC'),
                ),
                'creation_default_key' => 'title',
            ),
            'article' => array
            (
                'class' => 'midcom_db_article',
                'component' => 'net.nehmer.static',
                'headers' => array
                (
                    'title',
                ),
                'constraints' => array(),
                'searchfields' => array
                (
                    'title'
                ),
                'orders' => array
                (
                    array('title' => 'ASC'),
                    array('metadata.published' => 'ASC'),
                ),
                'id_field' => 'guid',
            ),
            'topic' => array
            (
                'class' => 'midcom_db_topic',
                'component' => 'midcom.admin.folder',
                'headers' => array
                (
                    'extra',
                    'component',
                ),
                'constraints' => array(),
                'searchfields' => array
                (
                    'extra',
                    'name',
                    'component',
                ),
                'orders' => array
                (
                    array('extra' => 'ASC'),
                    array('metadata.published' => 'ASC'),
                ),
                'generate_path_for' => 'extra',
            ),
            'group' => array
            (
                'class' => 'midcom_db_group',
                'component' => 'midgard.admin.asgard',
                'headers' => array
                (
                    'name',
                    'official',
                ),
                'constraints' => array(),
                'searchfields' => array
                (
                    'name',
                    'official',
                ),
                'orders' => array
                (
                    array('extra' => 'ASC'),
                    array('metadata.published' => 'ASC'),
                ),
                'id_field' => 'id',
                'generate_path_for' => 'name',
            ),
            'event' => array
            (
                'class' => 'net_nemein_calendar_event',
                'component' => 'net.nemein.calendar',
                'headers' => array
                (
                    'start',
                    'end',
                    'title',
                    'location',
                ),
                'constraints' => array(),
                'searchfields' => array
                (
                    'title',
                    'location',
                ),
                'orders' => array
                (
                    array('title' => 'ASC'),
                    array('start' => 'ASC'),
                    array('metadata.published' => 'ASC'),
                ),
                'creation_default_key' => 'title',
            ),
        );

        if (array_key_exists($this->clever_class, $clever_classes))
        {
            // debug_add("clever class {$this->clever_class} found!");

            $this->class = $clever_classes[$this->clever_class]['class'];
            $this->component = $clever_classes[$this->clever_class]['component'];

            if (! empty($this->component))
            {
                $_MIDCOM->componentloader->load($this->component);
            }

            if (empty($this->result_headers))
            {
                $this->result_headers = array();
                foreach ($clever_classes[$this->clever_class]['headers'] as $header_key)
                {
                    $header = array();
                    if ($this->component)
                    {
                        $header['title'] = $_MIDCOM->i18n->get_string($header_key, $this->component);
                    }
                    else
                    {
                        $header['title'] = $this->_l10n_midcom->get($header_key);
                    }
                    $header['name'] = $header_key;
                    $this->result_headers[] = $header;
                }
            }
            if (   is_null($this->generate_path_for)
                && isset($clever_classes[$this->clever_class]['generate_path_for']))
            {
                $this->generate_path_for = $clever_classes[$this->clever_class]['generate_path_for'];
            }
            if (empty($this->constraints))
            {
                $this->constraints = $clever_classes[$this->clever_class]['constraints'];
            }
            if (empty($this->searchfields))
            {
                $this->searchfields = $clever_classes[$this->clever_class]['searchfields'];
            }
            if (empty($this->orders))
            {
                $this->orders = $clever_classes[$this->clever_class]['orders'];
            }
            if (isset($clever_classes[$this->clever_class]['reflector_key']))
            {
                $this->reflector_key = $clever_classes[$this->clever_class]['reflector_key'];
            }
            if (!$this->id_field
                && isset($clever_classes[$this->clever_class]['id_field']))
            {
                $this->id_field = $clever_classes[$this->clever_class]['id_field'];
            }

            // debug_pop();
            return true;
        }
        else
        {
            // debug_add("clever class {$this->clever_class} not found in predefined list. Trying to use reflector");

            $matching_type = false;
            $matched_types = array();
            foreach ($_MIDGARD['schema']['types'] as $schema_type => $dummy)
            {
                //debug_add("schema type: {$schema_type}");
                $pos = strpos($schema_type, $this->clever_class);
                if ($pos !== false)
                {
                    // debug_add("found possible match: {$schema_type}");
                    $matched_types[] = $schema_type;
                }
            }

            // debug_print_r('$matched_types',$matched_types);

            if (count($matched_types) == 1)
            {
                $matching_type = $matched_types[0];
            }
            else
            {
                if ($this->clever_class == 'event')
                {
                    $matching_type = 'net_nemein_calendar_event';//'org_openpsa_event';
                    $this->creation_default_key = 'title';
                }
                else if ($this->clever_class == 'person')
                {
                    $matching_type = 'midgard_person';
                }
                else
                {
                    if (count($matched_types) > 0)
                    {
                        $matching_type = $matched_types[0];
                    }
                }
            }

            // debug_print_r('Decided to go with',$matching_type);

            if (! $matching_type)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("no matches found for {$this->clever_class}!");
                debug_pop();
                return false;
            }

            $midcom_reflector = new midcom_helper_reflector($matching_type);
            $mgd_reflector = new midgard_reflection_property($matching_type);

            $labels = array();

            $dummy_object = new $matching_type();
            $type_fields = array_keys(get_object_vars($dummy_object));
            // debug_print_r('type_fields',$type_fields);

            unset($type_fields['metadata']);
            foreach ($type_fields as $key)
            {
                // debug_add("processing type field {$key}");
                if ($mgd_reflector->is_link($key))
                {
                    // debug_add("type field {$key} is link");
                }

                if (in_array($key, array('title','firstname','lastname','name','email','start','end','location')))
                {
                    if (! in_array($key, $labels))
                    {
                        $labels[] = $key;
                    }
                }
            }

            if (empty($labels))
            {
                $label_properties = $midcom_reflector->get_label_property();
                // debug_print_r('$label_properties',$label_properties);
                if (is_array($label_properties))
                {
                    foreach ($label_properties as $key)
                    {
                        if (! in_array($key,array('id','guid')))
                        {
                            if (! in_array($key, $labels))
                            {
                                $labels[] = $key;
                            }
                        }
                    }
                }
            }

            $this->class = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($matching_type);
            //midgard_admin_asgard_reflector::resolve_baseclass($matching_type);
            $this->component = $_MIDCOM->dbclassloader->get_component_for_class($matching_type);
            //$matching_type;

            if (empty($this->constraints))
            {
                $this->constraints = array();
            }
            if (empty($this->searchfields))
            {
                $this->searchfields = $midcom_reflector->get_search_properties();
                if (empty($this->searchfields))
                {
                    //Special rules for objects that need them
                }
            }
            if (empty($this->orders))
            {
                $this->orders = array();
            }

            $reflection_l10n = $midcom_reflector->get_component_l10n();
            if (empty($this->result_headers))
            {
                $this->result_headers = array();
                foreach ($labels as $label)
                {
                    $header = array();
                    $header['title'] = $reflection_l10n->get($label);
                    $header['name'] = $label;
                    $this->result_headers[] = $header;
                }

                if (empty($this->result_headers))
                {
                    //Special rules for objects that need them
                }
            }

            if (   $this->creation_mode_enabled
                && empty($this->creation_default_key))
            {
                $this->creation_default_key = $this->result_headers[0]['name'];
            }

            /*debug_add("using class: {$this->class}");
            debug_add("using component: {$this->component}");
            debug_print_r('$this->searchfields',$this->searchfields);
            debug_print_r('$this->result_headers',$this->result_headers);

            debug_pop();*/
            return true;
        }

        //debug_pop();
        return false;
    }

    function _init_widget_options()
    {
        $this->_js_widget_options['widget_id'] = "'{$this->_element_id}'";
        $this->_js_widget_options['min_chars'] = $this->min_chars;
        $this->_js_widget_options['result_limit'] = 10;
        $this->_js_widget_options['renderer_callback'] = 'false';
        $this->_js_widget_options['result_headers'] = '[]';
        $this->_js_widget_options['allow_multiple'] = 'true';
        $this->_js_widget_options['id_field'] = "'$this->id_field'";
        $this->_js_widget_options['format_items'] = 'null';

        if ($this->generate_path_for)
        {
            $this->_js_widget_options['generate_path_for'] = "'{$this->generate_path_for}'";
        }

        if ($this->sortable)
        {
            $this->_js_widget_options['sortable'] = 'true';
        }

        if ($this->creation_mode_enabled)
        {
            $this->_js_widget_options['creation_mode'] = 'true';
            $this->_js_widget_options['creation_handler'] = "'{$this->creation_handler}'";
            $this->_js_widget_options['creation_default_key'] = "'{$this->creation_default_key}'";
        }

        if (isset($this->max_results))
        {
            $this->_js_widget_options['result_limit'] = $this->result_limit;
        }
        if (isset($this->renderer_callback))
        {
            $this->_js_widget_options['renderer_callback'] = $this->renderer_callback;
        }
        if (isset($this->allow_multiple))
        {
            $this->_js_widget_options['allow_multiple'] = 'false';
            if ($this->allow_multiple)
            {
                $this->_js_widget_options['allow_multiple'] = 'true';
            }
        }
        if (! empty($this->js_format_items))
        {
            $format_items = "{ ";
            $fi_count = count($this->js_format_items);
            $i = 0;
            foreach ($this->js_format_items as $k => $formatter)
            {
                $i++;
                $format_items .= "'{$k}': '{$formatter}'";

                if ($i == $fi_count)
                {
                    $format_items .= " ";
                }
                else
                {
                    $format_items .= ", ";
                }
            }
            $format_items .= "}";
            $this->_js_widget_options['format_items'] = $format_items;
        }

        $headers = "[ ";
        $header_count = count($this->result_headers);
        foreach ($this->result_headers as $k => $header_item)
        {
            $headers .= "{ ";

            $header_title = $_MIDCOM->i18n->get_string($_MIDCOM->i18n->get_string($header_item['title'], $this->component), 'midcom');

            $headers .= "title: '{$header_title}', ";
            $headers .= "name: '{$header_item['name']}' ";

            if (($k+1) == $header_count)
            {
                $headers .= " }";
            }
            else
            {
                $headers .= " }, ";
            }
        }
        $headers .= " ]";
        $this->_js_widget_options['result_headers'] = $headers;

        /*debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("js result_headers: {$headers}");
        debug_pop();*/

        $this->_generate_extra_params();
    }

    function _generate_extra_params()
    {
        $map = array
        (
            'component', 'class',
            '_callback_class', '_callback_args',
            '_renderer_callback_class', '_renderer_callback_args',
            'constraints', 'searchfields', 'orders',
            'result_headers', 'generate_path_for',
            'auto_wildcards',
            'reflector_key'
        );

        $params = array();
        $mk_cnt = count($map);
        foreach ($map as $k => $map_key)
        {
            $params[$map_key] = $this->$map_key;
        }

        $this->_js_widget_options['extra_params'] = "'" . base64_encode(serialize($params)) . "'";
    }

    /**
     * Internal helper for parsing the $_REQUEST data for including the elements requested via GET or POST
     *
     * @access private
     * @return Array
     */
    function _get_request_elements()
    {
        $results = array();

        // No results available
        if (!isset($_REQUEST["{$this->_element_id}_selections"]))
        {
            return $results;
        }

        foreach ($_REQUEST["{$this->_element_id}_selections"] as $guid => $value)
        {
            if (!$value)
            {
                continue;
            }

            $results[$guid] = $value;
        }

        return $results;
    }

    /**
     * Adds a simple search form and place holder for results.
     * Also adds static options to results.
     */
    function add_elements_to_form()
    {
        //debug_push_class(__CLASS__, __FUNCTION__);

        // Get url to search handler
        $nav = new midcom_helper_nav();
        $root_node = $nav->get_node($nav->get_root_node());
        if (   !$root_node
            || empty($root_node))
        {
            return;
        }
        $this->_handler_url = $root_node[MIDCOM_NAV_FULLURL] . 'midcom-exec-midcom.helper.datamanager2/chooser_handler.php';


        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'hidden',
            "{$this->_element_id}_handler_url",
            $this->_handler_url,
            array
            (
                'id' => "{$this->_element_id}_handler_url",
            )
        );
        foreach ($this->_js_widget_options as $key => $value)
        {
            $this->widget_elements[] = HTML_QuickForm::createElement
            (
                'hidden',
                "{$this->_element_id}_{$key}",
                $value,
                array
                (
                    'id' => "{$this->_element_id}_{$key}",
                )
            );
        }

        // Text input for the search box
        $search_input = HTML_QuickForm::createElement
        (
            'text',
            "{$this->_element_id}_search_input",
            $this->_translate($this->_field['title']),
            array
            (
                'class'         => 'shorttext chooser_widget_search_input',
                'id'            => "{$this->_element_id}_search_input",
                'style'         => "display: none;",
            )
        );

        if ($this->default_search)
        {
            $search_input->setValue($this->default_search);
            $this->_js_widget_options['default_search'] = true;
        }

        $this->widget_elements[] = $search_input;

        if ($this->creation_mode_enabled)
        {
            $dialog_id = $this->_element_id . '_creation_dialog';

            $dialog_html = "<div class=\"chooser_widget_creation_dialog\" id=\"{$dialog_id}\">\n";
            $dialog_html .= "    <div class=\"chooser_widget_creation_dialog_content_holder\">\n";
            $dialog_html .= "    </div>\n";
            $dialog_html .= "</div>\n";

            $button_html = "<div class=\"chooser_widget_create_button\" id=\"{$this->_element_id}_create_button\" style=\"display: none;\">\n";
            $button_html .= "</div>\n";

            $html = $button_html . $dialog_html;

            $this->widget_elements[] = HTML_QuickForm::createElement
            (
                'static',
                "{$this->_element_id}_creation_dialog_holder",
                '',
                $html
            );
        }

        $this->_jscript .= '<script type="text/javascript">';
        $this->_jscript .= 'jQuery().ready(function(){';

        $script = "jQuery('#{$this->_element_id}_search_input').midcom_helper_datamanager2_widget_chooser_widget('{$this->_handler_url}', {\n";
        if (!empty($this->_js_widget_options))
        {
            $opt_cnt = count($this->_js_widget_options);
            $i = 0;
            foreach ($this->_js_widget_options as $key => $value)
            {
                $i++;
                $script .= "{$key}: {$value}";
                if ($i < $opt_cnt)
                {
                    $script .= ",\n";
                }
            }
        }
        $script .= "});";
        $this->_jscript .= $script;

        // Add existing and static selections
        $existing_elements = $this->_type->selection;

        // Add to existing elements the ones requested (POST/GET) to this page
        $new_elements = $this->_get_request_elements();

        //debug_print_r('existing_elements',$existing_elements);

        // debug_print_r('static_options',$this->static_options);

        $elements = array_merge($this->static_options, $existing_elements, $new_elements);
        // debug_print_r('all elements to be added',$elements);
        // debug_pop();

        // $this->_static_items_html = "<noscript>\n";
        $this->_static_items_html .= "<table class=\"widget_chooser_static_items_table\">\n";
        $this->_static_items_html .= "    <thead>\n";
        $this->_static_items_html .= "        <tr>\n";

        if (   !empty($this->reflector_key)
            && !$this->result_headers)
        {
            $title = $_MIDCOM->i18n->get_string('Label', 'midcom');
            $this->_static_items_html .= "            <th class=\"label\">{$title}&nbsp;</th>\n";
        }
        else
        {
            foreach ($this->result_headers as $header_item)
            {
                $header_title = $_MIDCOM->i18n->get_string($_MIDCOM->i18n->get_string($header_item['title'], $this->component), 'midcom');
                $this->_static_items_html .= "            <th class=\"{$header_item['name']}\">{$header_title}&nbsp;</th>\n";
            }
        }

        $title = $_MIDCOM->i18n->get_string('Selected', 'midcom.helper.datamanager2');
        $this->_static_items_html .= "            <th class=\"selected\">{$title}&nbsp;</th>\n";
        $this->_static_items_html .= "        </tr>\n";
        $this->_static_items_html .= "    </thead>\n";
        $this->_static_items_html .= "    <tbody>\n";

        $ee_script = '';
        if ($this->_renderer_callback)
        {
            foreach ($elements as $key)
            {
                // debug_add("Passing key to renderer {$key}");
                $data = $this->_get_key_data($key);
                if ($data)
                {
                    // debug_add("Got data: {$data}");
                    $item = $this->_renderer_callback->render_data($data);
                    // debug_add("Got item: {$item}");
                    $ee_script .= "jQuery('#{$this->_element_id}_search_input').midcom_helper_datamanager2_widget_chooser_add_result_item({$data},'{$item}');\n";

                    $this->_add_existing_item_as_static($key);
                }
            }
        }
        else
        {
            foreach ($elements as $key)
            {
                // debug_add("Processing key {$key}");
                // $this->_add_existing_item_as_static($key);
                $data = $this->_get_key_data($key);
                if ($data)
                {
                    // debug_add("Got data: {$data}");
                    $ee_script .= "\n";
                    $ee_script .= "jQuery('#{$this->_element_id}_search_input')\n";
                    $ee_script .= ".midcom_helper_datamanager2_widget_chooser_add_result_item(\n";
                    $ee_script .= "    {$data},\n";
                    $ee_script .= "    this\n";
                    $ee_script .= ");\n";

                    $this->_add_existing_item_as_static($key);
                }
            }
        }

        $this->_jscript .= $ee_script;
        $this->_jscript .= "\njQuery('#" . $this->_element_id . "_search_input').midcom_helper_datamanager2_widget_chooser_adjust_height();";
        $this->_jscript .= '});';

        $this->_jscript .= "\nclose_dialog = function(widget_id){jQuery('#' + widget_id + '_creation_dialog').hide();};";
        $this->_jscript .= "\nadd_item = function(data, widget_id){jQuery('#' + widget_id + '_search_input').midcom_helper_datamanager2_widget_chooser_add_result_item(data);};";
        $this->_jscript .= '</script>';

        $this->_static_items_html .= "    </tbody>\n";
        $this->_static_items_html .= "</table>\n";
        // $this->_static_items_html .= "</noscript>\n";

        //$this->_form->addElement('static', "{$this->_element_id}_initscripts", '', $this->_jscript);

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'static',
            "{$this->_element_id}_initscripts",
            '',
            $this->_jscript
        );

        $this->widget_elements[] = HTML_QuickForm::createElement
        (
            'static',
            "{$this->_element_id}_noscript",
            '',
            $this->_static_items_html
        );

        $group = $this->_form->addGroup($this->widget_elements, $this->name, $this->_translate($this->_field['title']), '', array('class' => 'midcom_helper_datamanager2_widget_chooser'));

    }

    function _add_existing_item_as_static($key)
    {
        $object = $this->_get_key_data($key, false, true);
        $id_field = $this->id_field;
        $item_id = @$object->$id_field;

        if (   empty($item_id)
            || isset($this->_added_static_items[$item_id]))
        {
            return;
        }

        $this->_static_items_html .= "    <tr id=\"{$this->_element_id}_existing_item_{$item_id}_row\" class=\"chooser_widget_existing_item_static_row\">\n";

        if (   !empty($this->reflector_key)
            && !$this->result_headers)
        {
            $value = @$object->get_label();
            // $value = rawurlencode($value);
            //debug_add("adding header item: name=label value={$value}");
            $title = $_MIDCOM->i18n->get_string('label', 'midcom');
            $this->_static_items_html .= "<td class=\"label\">{$value}&nbsp;</td>\n";
        }
        else
        {
            foreach ($this->result_headers as $header_item)
            {
                $item_name = $header_item['name'];
                $value = @$object->$item_name;
                if ($item_name === $this->generate_path_for)
                {
                    $value = midcom_helper_datamanager2_widget_chooser::resolve_path($object, $value);
                }
                // $value = rawurlencode(utf8_decode($value));
                // debug_add("adding header item: name={$item_name} value={$value}");
        //
                $this->_static_items_html .= "<td class=\"{$item_name}\">{$value}&nbsp;</td>";
            }
        }

        $this->_static_items_html .= "<td>\n";
        $this->_static_items_html .= "<input type=\"checkbox\" name=\"{$this->_element_id}_selections[{$item_id}]\" id=\"{$this->_element_id}_existing_item_{$item_id}_value\" class=\"chooser_widget_existing_item_static_input\" checked=\"checked\"/>\n";
        $this->_static_items_html .= "</td>\n";

        $this->_static_items_html .= "</tr>\n";

        $this->_added_static_items[$item_id] = true;
    }

    function _get_object_values(&$object)
    {
        $name_components = array();
        foreach ($this->result_headers as $header_item)
        {
            $item_name = $header_item['name'];

            if (   !isset($object->$item_name)
                || empty($object->$item_name))
            {
                continue;
            }

            $value = $object->$item_name;
            $value = rawurlencode($value);
            $name_components[$item_name] = $value;
        }
        return $name_components;
    }

    function _resolve_object_name(&$object)
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        // debug_add("resolving object name from id {$object->id}");
        if (!class_exists('midcom_helper_reflector'))
        {
            return get_class($object) . " #{$object->id}";
        }
        $ref = new midcom_helper_reflector($object);
        return $ref->get_object_label($object);
    }

    function _object_to_jsdata(&$object)
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        // debug_add("converting object with id {$object->id} to jsdata");

        $id = @$object->id;
        $guid = @$object->guid;

        $jsdata = "{";

        $jsdata .= "id: '{$id}',";
        $jsdata .= "guid: '{$guid}',";
        $jsdata .= "pre_selected: true,";

        if (   !empty($this->reflector_key)
            && !$this->result_headers)
        {
            $value = @$object->get_label();
            $value = rawurlencode($value);
            //debug_add("adding header item: name=label value={$value}");
            $jsdata .= "label: '{$value}'";
        }
        else
        {
            $hi_count = count($this->result_headers);
            $i = 1;
            foreach ($this->result_headers as $header_item)
            {
                $item_name = $header_item['name'];
                $value = @$object->$item_name;
                if ($item_name === $this->generate_path_for)
                {
                    $value = midcom_helper_datamanager2_widget_chooser::resolve_path($object, $value);
                }
                $value = rawurlencode(utf8_decode($value));
                // debug_add("adding header item: name={$item_name} value={$value}");

                $tmp = str_replace('.', '_', $item_name);

                $jsdata .= "{$tmp}: '{$value}'";

                if ($i < $hi_count)
                {
                    $jsdata .= ", ";
                }

                $i++;
            }
        }

        $jsdata .= "}";

        // debug_pop();

        return $jsdata;
    }

    function _get_key_data($key, $in_render_mode=false, $return_object=false)
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        // debug_add("get_key_data for key: {$key}");
        if ($this->_callback)
        {
            // debug_add("Using callback to fetch key data");

            if ($in_render_mode)
            {
                // debug_pop();
                return $this->_callback->resolve_object_name($key);
            }

            $results = $this->_callback->get_key_data($key);

            if (! $results)
            {
                return false;
            }

            // debug_pop();

            if (   $this->_renderer_callback
                || $return_object)
            {
                return $results;
            }

            return $this->_object_to_jsdata($results);
        }

        // debug_add("Using clever class or predefined class");

        $_MIDCOM->auth->request_sudo();

        if (   isset($this->reflector_key)
            && !empty($this->reflector_key))
        {
            if ($this->reflector_key == 'buddy')
            {
                $this->class = 'org_openpsa_contacts_person_dba';
                $this->component = 'org.openpsa.contacts';
            }
        }

        if (!class_exists($this->class))
        {
            $_MIDCOM->componentloader->load_graceful($this->component);
        }

        $qb = @call_user_func(array($this->class, 'new_query_builder'));
        if (! $qb)
        {
            // debug_add("use midgard_query_builder");
            $qb = new midgard_query_builder($this->class);
        }

        //$qb->begin_group('OR');
        $qb->add_constraint($this->id_field, '=', $key);
        //$qb->add_constraint('guid', '=', $key);
        //$qb->end_group();

        $results = $qb->execute();
        // debug_print_r("Got results:",$results);

        $_MIDCOM->auth->drop_sudo();

        if (count($results) == 0)
        {
            // debug_add("Fetching data for key '{$key}' failed.");
            return false;
        }

        $object = $results[0];

        // debug_pop();
        if ($return_object)
        {
            return $object;
        }

        if ($in_render_mode)
        {
            return $this->_resolve_object_name($object);
        }

        if ($this->_renderer_callback)
        {
            return $object;
        }

        return $this->_object_to_jsdata($object);
    }


    function freeze()
    {
        foreach ($this->widget_elements as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->freeze();
            }
        }
    }

    /**
     * Unfreezes all form elements associated with the widget.
     *
     * The default implementation works on the default field name, you don't need to
     * override this function unless you have multiple widgets in the form.
     *
     * This maps to the HTML_QuickForm_element::unfreeze() function.
     */
    function unfreeze()
    {
        foreach ($this->widget_elements as $element)
        {
            if (method_exists($element, 'freeze'))
            {
                $element->unfreeze();
            }
        }
    }

    function is_frozen()
    {
        foreach ($this->widget_elements as $element)
        {
            if (method_exists($element, 'isFrozen')
                && !$element->isFrozen())
            {
                return false;
            }
        }
        return true;
    }

    /**
      * The defaults of the widget are mapped to the current selection.
      */
     function get_default()
     {
         // debug_push_class(__CLASS__, __FUNCTION__);
         //debug_print_r('this->_type',$this->_type);

         $defaults = array();
         foreach ($this->_type->selection as $key)
         {
             $defaults[$key] = true;
         }

         // debug_print_r('defaults',$defaults);
         // debug_pop();

         return array($this->name => $defaults);
     }

    /**
     * Reads the given get/post data and puts to type->selection
     */
    function sync_type_with_widget($results)
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        // debug_print_r('results:',$results);

        $this->_type->selection = array();
        if (!isset($results["{$this->_element_id}_selections"]))
        {
            return;
        }

        $real_results =& $results["{$this->_element_id}_selections"];
        if (is_array($real_results))
        {
            foreach ($real_results as $key => $value)
            {
                // debug_add("checking key {$key} with value ".var_dump($value));
                if (   $value != "0"
                    || $value != 0)
                {
                    // debug_add("adding key {$key} to selection");
                    $this->_type->selection[] = $key;
                }
            }
        }
        elseif (!$this->allow_multiple)
        {
            $this->_type->selection[] = $real_results;
        }

        if (   $this->sortable
            && isset($results[$this->_element_id])
            && isset($results[$this->_element_id]['sortable']))
        {
            $this->_type->sorted_order = array();

            foreach ($results[$this->_element_id]['sortable'] as $i => $id)
            {
                $this->_type->sorted_order[$id] = $i;
            }
        }

        // debug_print_r('real_results', $real_results);
        // debug_print_r('_type->selection', $this->_type->selection);
        // debug_pop();
    }

    function render_content()
    {
        // debug_push_class(__CLASS__, __FUNCTION__);
        echo "<table class=\"chooser_results\">\n";
        if (count($this->_type->selection) == 0)
        {
            echo "    <tr>\n";
            echo "        <td>" . $this->_translate('type select: no selection') . "</td>\n";
            echo "    </tr>\n";
        }
        else
        {
            foreach ($this->_type->selection as $key)
            {
                if (   !$key
                    && count($this->_type->selection) == 1)
                {
                    echo "    <tr>\n";
                    echo "        <td>" . $this->_translate('type select: no selection') . "</td>\n";
                    echo "    </tr>\n";
                    continue;
                }

                $data = rawurldecode($this->_get_key_data($key, true));
                echo "    <tr>\n";
                echo "        <td>{$data}</td>\n";
                echo "    </tr>";
            }
        }
        echo "</table>\n";

        // debug_pop();
    }

    /**
     * Statically callable helper method to resolve path for an object
     */
    function resolve_path(&$object, $title)
    {
        if (!class_exists('midcom_helper_reflector_tree'))
        {
            return $title;
        }
        return midcom_helper_reflector_tree::resolve_path($object);
    }
}
?>
