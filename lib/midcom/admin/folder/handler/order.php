<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

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
     * @var boolean
     */
    private $ajax = false;

    /**
     * Set the score.
     */
    private function _process_order_form(Request $request)
    {
        // Form has been handled if cancel has been pressed
        if ($request->request->has('f_cancel')) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('cancelled'));
            return new midcom_response_relocate('');
        }

        // If the actual score list hasn't been posted, return
        if (!$request->request->has('f_submit')) {
            return;
        }

        if ($request->request->has('f_navorder')) {
            $this->_topic->set_parameter('midcom.helper.nav', 'navorder', $request->request->get('f_navorder'));
        }

        // Success tells whether the update was successful or not. On default everything goes fine,
        // but when any errors are encountered, there will be a uimessage that will be shown.
        $success = true;

        // Total number of the entries
        $count = array_sum(array_map('count', $request->request->get('sortable')));

        // Loop through the sortables and store the new score
        foreach ($request->request->get('sortable') as $array) {
            foreach ($array as $identifier => $i) {
                $score_r = $count - (int) $i;

                if (!$this->_update_score($identifier, $score_r)) {
                    $success = false;
                }
            }
        }

        if ($success) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n_midcom->get('order saved'));
            return $this->get_workflow('viewer')->js_response('refresh_opener();');
        }
    }

    private function _update_score(string $identifier, $score) : bool
    {
        // Use the DB Factory to resolve the class and to get the object
        try {
            $object = midcom::get()->dbfactory->get_object_by_guid($identifier);
        } catch (midcom_error $e) {
            // This is probably a pseudo leaf, store the score to the current node
            $this->_topic->set_parameter('midcom.helper.nav.score', $identifier, $score);
            return true;
            // This will skip the rest of the handling
        }

        // Get the approval status (before setting score)
        $approval_status = $object->metadata->is_approved();

        //$metadata->set() calls update *AND* updates the metadata cache correctly, thus we use that instead of raw update
        if (!$object->metadata->set('score', $score)) {
            // Show an error message on an update failure
            $reflector = midcom_helper_reflector::get($object);
            $title = $reflector->get_class_label() . ' ' . $reflector->get_object_label($object);
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('failed to update %s due to: %s'), $title, midcom_connection::get_error_string()), 'error');
            return false;
        }

        // Approve if possible
        if (   $approval_status
            && $object->can_do('midcom:approve')) {
            $object->metadata->approve();
        }
        return true;
    }

    /**
     * Handler for setting the sort order
     */
    public function _handler_order(Request $request, array &$data)
    {
        $this->_topic->require_do('midgard:update');

        // These pages need no caching
        midcom::get()->cache->content->no_cache();

        // Process the form
        if ($response = $this->_process_order_form($request)) {
            return $response;
        }

        $this->ajax = $request->query->has('ajax');
        // Skip the page style on AJAX form handling
        if ($this->ajax) {
            $data['navorder'] = $request->request->get('f_navorder');
            midcom::get()->skip_page_style = true;
        } else {
            $data['navorder'] = (int) $this->_topic->get_parameter('midcom.helper.nav', 'navorder');
            // Add the view to breadcrumb trail
            $this->add_breadcrumb($this->router->generate('order'), $this->_l10n->get('order navigation'));

            // Hide the button in toolbar
            $this->_node_toolbar->hide_item($this->router->generate('order'));

            // Set page title
            $data['folder'] = $this->_topic;
            $title = sprintf($this->_l10n->get('order navigation in folder %s'), $this->_topic->get_label());
            midcom::get()->head->set_pagetitle($title);

            // Set the help object in the toolbar
            $help_toolbar = midcom::get()->toolbars->get_help_toolbar();
            $help_toolbar->add_help_item('navigation_order', 'midcom.admin.folder', null, null, 1);

            // jQuery sorting
            midcom::get()->head->enable_jquery_ui(['mouse', 'sortable']);

            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.admin.folder/jquery-postfix.js');

            // Custom styles
            $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/midcom-admin-order.css');
            $this->add_stylesheet(MIDCOM_STATIC_URL . '/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css');
        }
        return $this->get_workflow('viewer')->run($request);
    }

    /**
     * Show the sorting
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_order($handler_id, array &$data)
    {
        // Navorder list for the selection
        $data['navorder_list'] = [
            MIDCOM_NAVORDER_DEFAULT => $this->_l10n->get('default sort order'),
            MIDCOM_NAVORDER_TOPICSFIRST => $this->_l10n->get('folders first'),
            MIDCOM_NAVORDER_ARTICLESFIRST => $this->_l10n->get('pages first'),
            MIDCOM_NAVORDER_SCORE => $this->_l10n->get('by score'),
        ];

        if (!$this->ajax) {
            midcom_show_style('midcom-admin-folder-order-start');
        }

        $data['navigation'] = $this->_get_navigation_data();

        // Loop through each navigation type (node, leaf and mixed)
        foreach ($data['navigation'] as $key => $array) {
            $data['navigation_type'] = $key;
            $data['navigation_items'] = $array;
            midcom_show_style('midcom-admin-folder-order-type');
        }

        if (!$this->ajax) {
            midcom_show_style('midcom-admin-folder-order-end');
        }
    }

    private function _get_navigation_data() : array
    {
        $ret = [];
        // Initialize the midcom_helper_nav or navigation access point
        $nap = new midcom_helper_nav();

        switch ($this->_request_data['navorder']) {
            case MIDCOM_NAVORDER_DEFAULT:
                $ret['nodes'] = $nap->get_nodes($nap->get_current_node());
                break;

            case MIDCOM_NAVORDER_TOPICSFIRST:
                // Sort the array to have the nodes first
                $ret = [
                    'nodes' => [],
                    'leaves' => [],
                ];
                // Fall through

            case MIDCOM_NAVORDER_ARTICLESFIRST:
                // Sort the array to have the leaves first
                if (!isset($ret['leaves'])) {
                    $ret = [
                        'leaves' => [],
                        'nodes' => [],
                    ];
                }

                // Get the nodes
                $ret['nodes'] = $nap->get_nodes($nap->get_current_node());

                // Get the leafs
                $ret['leaves'] = $nap->get_leaves($nap->get_current_node());
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
