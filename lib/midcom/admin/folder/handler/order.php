<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sort navigation order.
 *
 * This handler enables drag'n'drop sorting of navigation
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_order extends midcom_baseclasses_components_handler
{
    /**
     * This function will set the score.
     *
     * @return boolean Indicating success
     */
    private function _process_order_form()
    {
        if (isset($_POST['f_navorder']))
        {
            $this->_topic->set_parameter('midcom.helper.nav', 'navorder', $_POST['f_navorder']);
        }

        // Form has been handled if cancel has been pressed
        if (isset($_POST['f_cancel']))
        {
            midcom::get('uimessages')->add($_MIDCOM->i18n->get_string('midcom.admin.folder'), $_MIDCOM->i18n->get_string('cancelled'));
            $_MIDCOM->relocate('');
            // This will exit
        }

        // If the actual score list hasn't been posted, return false
        if (!isset($_POST['f_submit']))
        {
            return false;
        }

        // Success tells whether the update was successful or not. On default everything goes fine,
        // but when any errors are encountered, there will be a uimessage that will be shown.
        $success = true;

        $count = 0;
        foreach ($_POST['sortable'] as $type_items)
        {
            // Total number of the entries
            $count += count($type_items);
        }

        // Loop through the sortables and store the new score
        foreach ($_POST['sortable'] as $array)
        {
            foreach ($array as $identifier => $i)
            {
                // Set the score reversed: the higher the value, the higher the rank
                $score_r = (int)($count - $i);

                if (!$this->_update_score($identifier, $score_r))
                {
                    $success = false;
                }
            }
        }

        if ($success)
        {
            midcom::get('uimessages')->add($_MIDCOM->i18n->get_string('midcom.admin.folder'), $_MIDCOM->i18n->get_string('order saved'));
            $_MIDCOM->relocate('');
            // This will exit
        }
    }

    private function _update_score($identifier, $score)
    {
        // Use the DB Factory to resolve the class and to get the object
        try
        {
            $object = midcom::get('dbfactory')->get_object_by_guid($identifier);
        }
        catch (midcom_error $e)
        {
            // This is probably a pseudo leaf, store the score to the current node
            $this->_topic->set_parameter('midcom.helper.nav.score', $identifier, $score);
            return true;
            // This will skip the rest of the handling
        }

        // Get the original approval status and update metadata reference
        $metadata = midcom_helper_metadata::retrieve($object);
        if (!is_object($metadata))
        {
            throw new midcom_error("Could not fetch metadata for object {$object->guid}");
        }
        // Make sure this is reference to correct direction (from our point of view)
        $metadata->__object =& $object;

        // Get the approval status if metadata object is available
        $approval_status = false;
        if ($metadata->is_approved())
        {
            $approval_status = true;
        }

        $object->metadata->score = $score;

        //$metadata->set() calls update *AND* updates the metadata cache correctly, thus we use that in stead of raw update
        if (!$metadata->set('score', $object->metadata->score))
        {
            // Show an error message on an update failure
            $reflector =& midcom_helper_reflector::get($object);
            $title = $reflector->get_class_label() . ' ' . $reflector->get_object_label($object);
            midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('failed to update %s due to: %s'), $title, midcom_connection::get_error_string()), 'error');
            return false;
        }

        // Approve if possible
        if (   $approval_status
            && $object->can_do('midcom:approve'))
        {
            if (!isset($metadata))
            {
                $metadata = midcom_helper_metadata::retrieve($object);
            }
            $metadata->approve();
        }
        return true;
    }

    /**
     * Handler for setting the sort order
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_order($handler_id, array $args, array &$data)
    {
        // jQuery sorting
        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL.'/jQuery/jquery.form.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.sortable.min.js');

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL.'/midcom.admin.folder/jquery-postfix.js');

        // These pages need no caching
        midcom::get('cache')->content->no_cache();

        // Custom styles
        $this->add_stylesheet(MIDCOM_STATIC_URL.'/midcom.admin.folder/midcom-admin-order.css');

        $this->_topic->require_do('midgard:update');

        // Process the form
        $this->_process_order_form();

        // Add the view to breadcrumb trail
        $this->add_breadcrumb('__ais/folder/order/', $this->_l10n->get('order navigation'));

        // Hide the button in toolbar
        $this->_node_toolbar->hide_item('__ais/folder/order/');

        // Set page title
        $data['folder'] = $this->_topic;
        $folder_title = $data['folder']->extra;
        if (!$folder_title)
        {
            $folder_title = $data['folder']->name;
        }
        $data['title'] = sprintf($_MIDCOM->i18n->get_string('order navigation in folder %s', 'midcom.admin.folder'), $folder_title);
        $_MIDCOM->set_pagetitle($data['title']);

        // Set the help object in the toolbar
        $help_toolbar = $_MIDCOM->toolbars->get_help_toolbar();
        $help_toolbar->add_help_item('navigation_order', 'midcom.admin.folder', null, null, 1);

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midcom.admin.folder');

        // Skip the page style on AJAX form handling
        if (isset($_GET['ajax']))
        {
            $_MIDCOM->skip_page_style = true;
        }
    }

    /**
     * Show the sorting
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_order($handler_id, array &$data)
    {
        $data['navorder'] = $this->_topic->get_parameter('midcom.helper.nav', 'navorder');

        // Navorder list for the selection
        $data['navorder_list'] = array
        (
            MIDCOM_NAVORDER_DEFAULT => $_MIDCOM->i18n->get_string('default sort order', 'midcom.admin.folder'),
            MIDCOM_NAVORDER_TOPICSFIRST => $_MIDCOM->i18n->get_string('folders first', 'midcom.admin.folder'),
            MIDCOM_NAVORDER_ARTICLESFIRST => $_MIDCOM->i18n->get_string('pages first', 'midcom.admin.folder'),
            MIDCOM_NAVORDER_SCORE => $_MIDCOM->i18n->get_string('by score', 'midcom.admin.folder'),
        );

        if (!isset($_GET['ajax']))
        {
            midcom_show_style('midcom-admin-folder-order-start');
        }

        $data['navigation'] = $this->_get_navigation_data();

        // Loop through each navigation type (node, leaf and mixed)
        foreach ($data['navigation'] as $key => $array)
        {
            $data['navigation_type'] = $key;
            $data['navigation_items'] = $array;
            midcom_show_style('midcom-admin-folder-order-type');
        }

        if (!isset($_GET['ajax']))
        {
            midcom_show_style('midcom-admin-folder-order-end');
        }
    }

    private function _get_navigation_data()
    {
        $ret = array();
        // Initialize the midcom_helper_nav or navigation access point
        $nap = new midcom_helper_nav();

        switch ((int) $this->_topic->get_parameter('midcom.helper.nav', 'navorder'))
        {
            case MIDCOM_NAVORDER_DEFAULT:
                $ret['nodes'] = array();
                $nodes = $nap->list_nodes($nap->get_current_node());

                foreach ($nodes as $id => $node_id)
                {
                    $node = $nap->get_node($node_id);
                    $node[MIDCOM_NAV_TYPE] = 'node';
                    $ret['nodes'][$id] = $node;
                }
                break;

            case MIDCOM_NAVORDER_TOPICSFIRST:
                // Sort the array to have the nodes first
                $ret = array
                (
                    'nodes' => array(),
                    'leaves' => array(),
                );
                // Fall through

            case MIDCOM_NAVORDER_ARTICLESFIRST:
                // Sort the array to have the leaves first

                if (!isset($ret['leaves']))
                {
                    $ret = array
                    (
                        'leaves' => array(),
                        'nodes' => array(),
                    );
                }

                // Get the nodes
                $nodes = $nap->list_nodes($nap->get_current_node());

                foreach ($nodes as $id => $node_id)
                {
                    $node = $nap->get_node($node_id);
                    $node[MIDCOM_NAV_TYPE] = 'node';
                    $ret['nodes'][$id] = $node;
                }

                // Get the leafs
                $leaves = $nap->list_leaves($nap->get_current_node());

                foreach ($leaves as $id => $leaf_id)
                {
                    $leaf = $nap->get_leaf($leaf_id);
                    $leaf[MIDCOM_NAV_TYPE] = 'leaf';
                    $ret['leaves'][$id] = $leaf;
                }
                break;

            case MIDCOM_NAVORDER_SCORE:
            default:
                $ret['mixed'] = array();

                // Get the navigation items
                $items = $nap->list_child_elements($nap->get_current_node());

                foreach ($items as $id => $item)
                {
                    if ($item[MIDCOM_NAV_TYPE] === 'node')
                    {
                        $element = $nap->get_node($item[MIDCOM_NAV_ID]);
                    }
                    else
                    {
                        $element = $nap->get_leaf($item[MIDCOM_NAV_ID]);
                    }

                    // Store the type information
                    $element[MIDCOM_NAV_TYPE] = $item[MIDCOM_NAV_TYPE];

                    $ret['mixed'][] = $element;
                }
                break;
        }

        return $ret;
    }
}
?>
