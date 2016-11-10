<?php
/**
 * @author tarjei huse
 * @package midcom.helper.imagepopup
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This handler shows the attachments attached to object $object.
 *
 * @package midcom.helper.imagepopup
 */
class midcom_helper_imagepopup_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * The datamanager controller
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * Search results
     */
    private $_search_results = array();

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->require_valid_user();
        midcom::get()->skip_page_style = true;

        if (!$this->_config->get('enable_page')) {
            if (   $handler_id == '____ais-imagepopup-list_object'
                || $handler_id == '____ais-imagepopup-list_folder'
                || $handler_id == '____ais-imagepopup-list_unified') {
                return new midcom_response_relocate('__ais/imagepopup/unified/default/');
            }
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL ."/midcom.helper.imagepopup/styling.css", 'screen');

        $data['schema_name'] = $args[0];
        $data['filetype'] = $args[1];
        $data['object'] = null;
        $data['folder'] = $this->_topic;

        if (   $handler_id != '____ais-imagepopup-list_folder_noobject'
            && isset($args[2])) {
            $data['object'] = midcom::get()->dbfactory->get_object_by_guid($args[2]);
        }

        switch ($handler_id) {
            case '____ais-imagepopup-list_folder_noobject':
            case '____ais-imagepopup-list_folder':
                $data['list_type'] = 'folder';
                $data['list_title'] = $this->_l10n->get('folder attachments');
                break;

            case '____ais-imagepopup-list_object':
                $data['list_type'] = 'page';
                $data['list_title'] = $this->_l10n->get('page attachments');
                break;

            case '____ais-imagepopup-list_unified_noobject':
            case '____ais-imagepopup-list_unified':
                $data['list_type'] = 'unified';
                $data['list_title'] = $this->_l10n->get('unified search');
                $data['query'] = (array_key_exists('query', $_REQUEST) ? $_REQUEST['query'] : '');
                break;
        }

        midcom::get()->head->set_pagetitle($data['list_title']);

        if ($data['list_type'] != 'unified') {
            $this->_create_controller($data);
        } elseif ($data['query'] != '') {
            $this->_run_search($data);
        }

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.imagepopup/functions.js");

        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midcom.helper.imagepopup');
    }

    private function _create_controller(array $data)
    {
        // Run datamanager for handling the images
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb = $this->_load_schema();
        $this->_controller->schemaname = $data['schema_name'];

        if ($data['list_type'] == 'page') {
            $this->_controller->set_storage($data['object']);
        } else {
            $this->_controller->set_storage($data['folder']);
        }

        $this->_controller->initialize();
        $this->_request_data['form'] = $this->_controller;
        switch ($this->_controller->process_form()) {
            case 'cancel':
                midcom::get()->head->add_jsonload("top.tinymce.activeEditor.windowManager.close();");
                break;
        }
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
     * @param array &$data The local request data.
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

        if (count($this->_search_results) > 0) {
            foreach ($this->_search_results as $result) {
                $this->_request_data['result'] = $result;
                midcom_show_style('midcom_helper_imagepopup_search_result_item');
            }
        }

        midcom_show_style('midcom_helper_imagepopup_search_result_end');
    }

    /**
     * Loads the schema.
     */
    private function _load_schema()
    {
        return array(
            $this->_request_data['schema_name'] => new midcom_helper_datamanager2_schema(
                array(
                    $this->_request_data['schema_name'] => array(
                        'description' => 'generated schema',
                        'fields' => array(
                            'midcom_helper_imagepopup_images' => array(
                                'title' => $this->_l10n->get('images'),
                                'storage' => null,
                                'type' => 'images',
                                'widget' => 'images',
                                'widget_config' => array(
                                    'set_name_and_title_on_upload' => false
                                ),
                            ),

                            'midcom_helper_imagepopup_files' => array(
                                'title' => $this->_l10n->get('files'),
                                'storage' => null,
                                'type' => 'blobs',
                                'widget' => 'downloads',
                            )
                        )
                    )
                )
            )
        );
    }
}
