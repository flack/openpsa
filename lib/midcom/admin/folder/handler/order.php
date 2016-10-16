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
     * Set the score.
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
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n_midcom->get('cancelled'));
            midcom::get()->relocate('');
            // This will exit
        }

        // If the actual score list hasn't been posted, return
        if (!isset($_POST['f_submit']))
        {
            return;
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
                $score_r = $count - (int) $i;

                if (!$this->_update_score($identifier, $score_r))
                {
                    $success = false;
                }
            }
        }

        if ($success)
        {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n_midcom->get('order saved'));
            midcom::get()->relocate('');
            // This will exit
        }
    }

    private function _update_score($identifier, $score)
    {
        // Use the DB Factory to resolve the class and to get the object
        try
        {
            $object = midcom::get()->dbfactory->get_object_by_guid($identifier);
        }
        catch (midcom_error $e)
        {
            // This is probably a pseudo leaf, store the score to the current node
            $this->_topic->set_parameter('midcom.helper.nav.score', $identifier, $score);
            return true;
            // This will skip the rest of the handling
        }

        // Get the approval status (before setting score)
        $approval_status = $object->metadata->is_approved();

        //$metadata->set() calls update *AND* updates the metadata cache correctly, thus we use that instead of raw update
        if (!$object->metadata->set('score', $score))
        {
            // Show an error message on an update failure
            $reflector = midcom_helper_reflector::get($object);
            $title = $reflector->get_class_label() . ' ' . $reflector->get_object_label($object);
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('failed to update %s due to: %s'), $title, midcom_connection::get_error_string()), 'error');
            return false;
        }

        // Approve if possible
        if (   $approval_status
            && $object->can_do('midcom:approve'))
        {
            $object->metadata->approve();
        }
        return true;
    }

    /**
     * Handler for setting the sort order
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_order($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:update');

        // These pages need no caching
        midcom::get()->cache->content->no_cache();

        // Process the form
        $this->_process_order_form();

        // Skip the page style on AJAX form handling
        if (isset($_GET['ajax']))
        {
            midcom::get()->skip_page_style = true;
        }
        else
        {
            // Add the view to breadcrumb trail
            $this->add_breadcrumb('__ais/folder/order/', $this->_l10n->get('order navigation'));

            // Hide the button in toolbar
            $this->_node_toolbar->hide_item('__ais/folder/order/');

            // Set page title
            $data['folder'] = $this->_topic;
            $data['title'] = sprintf($this->_l10n->get('order navigation in folder %s'), $this->_topic->get_label());
            midcom::get()->head->set_pagetitle($data['title']);

            // Set the help object in the toolbar
            $help_toolbar = midcom::get()->toolbars->get_help_toolbar();
            $help_toolbar->add_help_item('navigation_order', 'midcom.admin.folder', null, null, 1);

            // jQuery sorting
            midcom::get()->head->enable_jquery();

            midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/core.min.js');
            midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/widget.min.js');
            midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/mouse.min.js');
            midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/sortable.min.js');

            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL.'/midcom.admin.folder/jquery-postfix.js');

            // Custom styles
            $this->add_stylesheet(MIDCOM_STATIC_URL.'/midcom.admin.folder/midcom-admin-order.css');
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
        $data['navorder'] = (int) $this->_topic->get_parameter('midcom.helper.nav', 'navorder');

        // Navorder list for the selection
        $data['navorder_list'] = array
        (
            MIDCOM_NAVORDER_DEFAULT => $this->_l10n->get('default sort order'),
            MIDCOM_NAVORDER_TOPICSFIRST => $this->_l10n->get('folders first'),
            MIDCOM_NAVORDER_ARTICLESFIRST => $this->_l10n->get('pages first'),
            MIDCOM_NAVORDER_SCORE => $this->_l10n->get('by score'),
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

        switch ($this->_request_data['navorder'])
        {
            case MIDCOM_NAVORDER_DEFAULT:
                $nodes = $nap->list_nodes($nap->get_current_node());
                $ret['nodes'] = array_map(array($nap, 'get_node'), $nodes);
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
                $ret['nodes'] = array_map(array($nap, 'get_node'), $nodes);

                // Get the leafs
                $leaves = $nap->list_leaves($nap->get_current_node());
                $ret['leaves'] = array_map(array($nap, 'get_leaf'), $leaves);
                break;

            case MIDCOM_NAVORDER_SCORE:
            default:
                // Get the navigation items
                $ret['mixed'] = $nap->list_child_elements($nap->get_current_node());
                break;
        }

        return $ret;
    }
}
