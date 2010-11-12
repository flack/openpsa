<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: folder_management.php 24773 2010-01-18 08:15:45Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Folder management class.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_folder_management extends midcom_baseclasses_components_handler
{
    /**
     * Anchor prefix stores the link back to the edited content topic
     *
     * @access private
     * @var string
     */
    var $_anchor_prefix = null;

    /**
     * Simple constructor, which only initializes the parent constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the object title of the content topic.
     *
     * @return string containing the content topic title
     */
    function _get_object_title($object)
    {
        $title = '';
        if (array_key_exists('title', $object))
        {
            $title = $object->title;
        }
        else if (is_a($object, 'midcom_db_topic'))
        {
            $title = $object->extra;
        }
        else if (array_key_exists('name', $object))
        {
            $title = $object->name;
        }
        else
        {
            $title = get_class($object) . " GUID {$object->guid}";
        }
        return $title;
    }

    /**
     * Initializes the context data and toolbar objects
     *
     * @access private
     */
    function _on_initialize()
    {
        $config = $this->_request_data['plugin_config'];
        if ($config)
        {
            foreach ($config as $key => $value)
            {
                $this->$key = $value;
            }
        }
        $this->_anchor_prefix = $this->_request_data['plugin_anchorprefix'];

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midcom.admin.folder');

        $this->_request_data['folder'] = $this->_topic;

        if (!array_key_exists($this->_topic->component, $_MIDCOM->componentloader->manifests))
        {
            $this->_topic->component = 'midcom.core.nullcomponent';
        }
    }

    /**
     * Get the plugin handlers, which act alike with Request Switches of MidCOM
     * Baseclasses Components (midcom.baseclasses.components.request)
     *
     * @access public
     * @return mixed Array of the plugin handlers
     */
    function get_plugin_handlers()
    {
        $_MIDCOM->load_library('midcom.admin.folder');
        $return = array
        (
            /**
             * Basic functionalities such as creation, editing and deleting
             * topic objects.
             */
            /**
             * Create a new topic
             *
             * Match /create/
             */
            'create' => array
            (
                'handler' => array('midcom_admin_folder_handler_edit', 'edit'),
                'fixed_args' => array ('create'),
            ),

            /**
             * Edit a topic
             *
             * Match /edit/
             */
            'edit' => array
            (
                'handler' => array('midcom_admin_folder_handler_edit', 'edit'),
                'fixed_args' => array ('edit'),
            ),

            /**
             * Delete a topic
             *
             * Match /delete/
             */
            'delete' => array
            (
                'handler' => array('midcom_admin_folder_handler_delete', 'delete'),
                'fixed_args' => array ('delete'),
            ),

            /**
             * Approval pseudo locations, which redirect back to the original page
             * after saving the new status.
             */
            /**
             * Approve a topic object
             *
             * Match /metadata/approve/
             */
            'approve' => array
            (
                'handler' => array('midcom_admin_folder_handler_approvals', 'approval'),
                'fixed_args' => array ('approve'),
            ),

            /**
             * Unapprove a topic object
             *
             * Match /metadata/unapprove/
             */
            'unapprove' => array
            (
                'handler' => array('midcom_admin_folder_handler_approvals', 'approval'),
                'fixed_args' => array ('unapprove'),
            ),

            /**
             * Miscellaneous other functionalities
             */
            /**
             * Metadata editing
             *
             * Match /metadata/<object guid>/
             */
            'metadata' => array
            (
                'handler' => array('midcom_admin_folder_handler_metadata', 'metadata'),
                'fixed_args' => array ('metadata'),
                'variable_args' => 1,
            ),

            /**
             * Object moving
             *
             * Match /move/<object guid>/
             */
            'move' => array
            (
                'handler' => array('midcom_admin_folder_handler_move', 'move'),
                'fixed_args' => array ('move'),
                'variable_args' => 1,
            ),

            // Match /order/
            'order' => array
            (
                'handler' => array('midcom_admin_folder_handler_order', 'order'),
                'fixed_args' => array ('order'),
            ),
        );
        if ($GLOBALS['midcom_config']['symlinks'])
        {
            /**
             * Create a new topic symlink
             * 
             * Match /createlink/
             */
            $return['createlink'] = array
            (
                'handler' => array('midcom_admin_folder_handler_edit', 'edit'),
                'fixed_args' => array ('createlink'),
            );
        }
        return $return;
    }

    /**
     * Static method to list names of the non-purecore components
     *
     * @access public
     * @param string $parent_component  Name of the parent component, which will pop the item first on the list
     * @return mixed Array containing names of the components
     */
    function get_component_list($parent_component = '')
    {
        $components = array ();

        // Loop through the list of components of component loader
        foreach ($_MIDCOM->componentloader->manifests as $manifest)
        {
            // Skip purecode components
            if ($manifest->purecode)
            {
                continue;
            }

            // Skip components beginning with midcom or midgard
            if (   preg_match('/^(midcom|midgard)\./', $manifest->name)
                && $manifest->name != 'midcom.helper.search')
            {
                continue;
            }

            // Skip components not ported to 2.6
            if (   !is_array($manifest->_raw_data)
                || !array_key_exists('package.xml', $manifest->_raw_data))
            {
                continue;
            }

            if (array_key_exists('description', $manifest->_raw_data['package.xml']))
            {
                $description = $_MIDCOM->i18n->get_string($manifest->_raw_data['package.xml']['description'], $manifest->name);
            }
            else
            {
                $description = '';
            }

            $components[$manifest->name] = array
            (
                'name'        => $manifest->get_name_translated(),
                'description' => $description,
                'state'       => @$manifest->state,
                'version'     => $manifest->version,
            );
        }

        // Sort the components in alphabetical order (by key i.e. component class name)
        asort($components);

        // Set the parent component to be the first if applicable
        if (   $parent_component !== ''
            && array_key_exists($parent_component, $components))
        {
            $temp = array();
            $temp[$parent_component] = $components[$parent_component];
            unset($components[$parent_component]);

            $components = array_merge($temp, $components);
        }

        return $components;
    }

    /**
     * Static method for populating user interface for editing and creating topics
     *
     * @access public
     * @static
     * @return Array Containing a list of components
     */
    function list_components($parent_component = '', $all = false)
    {
        $list = array();

        if ($urltopic = end($_MIDCOM->get_context_data(MIDCOM_CONTEXT_URLTOPICS)))
        {
            if (empty($urltopic->component))
            {
                $list[''] = '';
            }
        }

        foreach (midcom_admin_folder_folder_management::get_component_list() as $component => $details)
        {
            // TODO: configuration options for either excluding or including components to the list
            if (   isset($GLOBALS['midcom_config']['component_listing_allowed'])
                && is_array($GLOBALS['midcom_config']['component_listing_allowed'])
                && !in_array($component, $GLOBALS['midcom_config']['component_listing_allowed'])
                && $component !== $parent_component
                && !$all)
            {
                continue;
            }

            if (   isset($GLOBALS['midcom_config']['component_listing_excluded'])
                && is_array($GLOBALS['midcom_config']['component_listing_excluded'])
                && in_array($component, $GLOBALS['midcom_config']['component_listing_excluded'])
                && $component !== $parent_component
                && !$all)
            {
                continue;
            }

            $list[$component] = "{$details['name']} ({$component} {$details['version']})";
        }

        return $list;
    }

    /**
     * Static method for listing available style templates
     *
     * @access public
     */
    function list_styles($up = 0, $prefix = '/', $spacer = '')
    {
        static $style_array = array();

        $style_array[''] = $_MIDCOM->i18n->get_string('default', 'midcom.admin.folder');

        // Give an option for creating a new layout template
        $style_array['__create'] = $_MIDCOM->i18n->get_string('new layout template', 'midcom.admin.folder');

        if (   $GLOBALS['midcom_config']['styleengine_relative_paths']
            && $up == 0)
        {
            // Relative paths in use, start seeking from under the style used for the Midgard host
            $up = $_MIDGARD['style'];
        }

        $qb = midcom_db_style::new_query_builder();
        $qb->add_constraint('up', '=', $up);
        $styles = $qb->execute();

        foreach ($styles as $style)
        {
            $style_string = "{$prefix}{$style->name}";

            // Hide common unwanted material with heuristics
            if (preg_match('/(asgard|aegir|empty|spider|admin site)/i', $style_string))
            {
                continue;
            }

            $style_array[$style_string] = "{$spacer}{$style->name}";
            midcom_admin_folder_folder_management::list_styles($style->id, $style_string . '/', $spacer . '&nbsp;&nbsp;');
        }

        return $style_array;
    }

    /**
     * Checks if the folder has finite (healthy) tree below it.
     *
     * @access public
     * @param object $topic The folder to be checked.
     * @return boolean Indicating success
     */
    function is_child_listing_finite($topic, $stop = array())
    {
        if (!empty($topic->symlink))
        {
            $_MIDCOM->auth->request_sudo('midcom.admin.folder');
            $target_topic = new midcom_db_topic($topic->symlink);
            if ($target_topic && $target_topic->guid)
            {
                $topic = $target_topic;
            }
            else
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Could not get target for symlinked topic #{$topic->id}: " .
                    midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
            }
            $_MIDCOM->auth->drop_sudo();
        }

        if (in_array($topic->id, $stop))
        {
            return false;
        }

        $stop[] = $topic->id;

        $_MIDCOM->auth->request_sudo('midcom.admin.folder');
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $topic->id);
        $results = $qb->execute();
        $_MIDCOM->auth->drop_sudo();

        foreach ($results as $topic)
        {
            if (!midcom_admin_folder_folder_management::is_child_listing_finite($topic, $stop))
            {
                return false;
            }
        }

        return true;
    }
}
?>