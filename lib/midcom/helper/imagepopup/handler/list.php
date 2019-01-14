<?php
/**
 * @author tarjei huse
 * @package midcom.helper.imagepopup
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * This handler shows the attachments attached to object $object.
 *
 * @package midcom.helper.imagepopup
 */
class midcom_helper_imagepopup_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * Search results
     */
    private $_search_results = [];

    /**
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param string $filetype The file type
     * @param array $data The local request data.
     * @param string $guid The object GUID
     */
    public function _handler_list(Request $request, $handler_id, $filetype, array &$data, $guid = null)
    {
        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->require_valid_user();
        midcom::get()->skip_page_style = true;

        $this->add_stylesheet(MIDCOM_STATIC_URL ."/midcom.helper.imagepopup/styling.css", 'screen');

        $data['filetype'] = $filetype;
        $data['object'] = null;
        $data['folder'] = $this->_topic;

        if (isset($guid)) {
            $data['object'] = midcom::get()->dbfactory->get_object_by_guid($guid);
        }

        switch ($handler_id) {
            case 'list_folder_noobject':
            case 'list_folder':
                $data['list_type'] = 'folder';
                $data['list_title'] = $this->_l10n->get('folder attachments');
                break;

            case 'list_object':
                $data['list_type'] = 'page';
                $data['list_title'] = $this->_l10n->get('page attachments');
                break;

            case 'list_unified_noobject':
            case 'list_unified':
                $data['list_type'] = 'unified';
                $data['list_title'] = $this->_l10n->get('unified search');
                $data['query'] = $request->query->get('query', '');
                break;
        }

        midcom::get()->head->set_pagetitle($data['list_title']);

        if ($data['list_type'] != 'unified') {
            $data['form'] = $this->load_controller($request, $data);
        } elseif ($data['query'] != '') {
            $this->_run_search($data);
        }

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.imagepopup/functions.js");

        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midcom.helper.imagepopup');
    }

    private function load_controller(Request $request, array $data)
    {
        $dm = datamanager::from_schemadb($this->_config->get('schemadb'));
        if ($data['list_type'] == 'page') {
            $dm->set_storage($data['object'], 'default');
        } else {
            $dm->set_storage($data['folder'], 'default');
        }
        $controller = $dm->get_controller();
        if ($controller->handle($request) === 'cancel') {
            midcom::get()->head->add_jsonload("top.tinymce.activeEditor.windowManager.close();");
        }
        return $controller;
    }

    private function _run_search(array $data)
    {
        $qb = midcom_db_attachment::new_query_builder();
        $query = str_replace('*', '%', $data['query']);
        $qb->begin_group('OR');
        $qb->add_constraint('name', 'LIKE', $query);
        $qb->add_constraint('title', 'LIKE', $query);
        $qb->add_constraint('mimetype', 'LIKE', $query);
        $qb->end_group();

        $this->_search_results = $qb->execute();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['navlinks'] = midcom_helper_imagepopup_viewer::get_navigation($data);
        midcom_show_style('midcom_helper_imagepopup_init');
        if ($data['list_type'] == 'unified') {
            midcom_show_style('midcom_helper_imagepopup_search');
            $this->_show_search_results();
        } else {
            midcom_show_style('midcom_helper_imagepopup_list');
        }
        midcom_show_style('midcom_helper_imagepopup_finish');
    }

    private function _show_search_results()
    {
        midcom_show_style('midcom_helper_imagepopup_search_result_start');

        foreach ($this->_search_results as $result) {
            $this->_request_data['result'] = $result;
            midcom_show_style('midcom_helper_imagepopup_search_result_item');
        }

        midcom_show_style('midcom_helper_imagepopup_search_result_end');
    }
}
