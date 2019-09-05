<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Baseclass for reports handler, provides some common methods
 *
 * @package org.openpsa.reports
 */
abstract class org_openpsa_reports_handler_base extends midcom_baseclasses_components_handler
{
    protected $module;

    /**
     * @param array $args The argument list.
     * @param array $data The local request data.
     */
    public function _handler_generator(array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if ($response = $this->_generator_load_redirect($args)) {
            return $response;
        }
        $this->_handler_generator_style();
        if ($data['query']->title) {
            $data['title'] = $data['query']->title;
        }
    }

    abstract public function _show_generator($handler_id, array &$data);

    /**
     * @param Request $request The request object
     * @param array $data The local request data.
     */
    public function _handler_generator_get(Request $request, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        // NOTE: This array must be a same format as we get from DM get_array() method
        $data['query_data'] = $request->query->get('org_openpsa_reports_query_data');

        if (empty($data['query_data'])) {
            throw new midcom_error('query data not present or invalid');
        }
        $data['filename'] = 'get';

        $this->_handler_generator_style();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_generator_get($handler_id, array &$data)
    {
        $this->_show_generator($handler_id, $data);
    }

    /**
     * @param org_openpsa_reports_query_dba $query
     * @return \midcom\datamanager\datamanager
     */
    private function load_datamanager(org_openpsa_reports_query_dba $query)
    {
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_queryform_' . $this->module));
        $dm->set_storage($query, 'default');
        return $dm;
    }

    /**
     * @param Request $request The request object
     * @param array $args The argument list.
     * @param array $data The local request data.
     */
    public function _handler_query_form(Request $request, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if (isset($args[0])) {
            $data['query'] = new org_openpsa_reports_query_dba($args[0]);
            $data['query']->require_do('midgard:update');
        } else {
            $data['query']= new org_openpsa_reports_query_dba();
            $data['query']->component = $this->_component;
        }

        $data['controller'] = $this->load_datamanager($data['query'])->get_controller();

        // Process the form
        switch ($data['controller']->handle($request)) {
            case 'save':
                // Relocate to report view
                return new midcom_response_relocate($this->module . '/' . $this->_request_data['query']->guid . "/");

            case 'cancel':
                return new midcom_response_relocate('');
        }

        if ($data['query']->id) {
            $breadcrumb_label = sprintf($this->_l10n->get('edit report %s'), $data['query']->title);
        } else {
            $breadcrumb_label = $this->_l10n->get('define custom report');
        }
        $this->add_breadcrumb("", $breadcrumb_label);

        return $this->show("{$this->module}_query_form");
    }

    protected function _generator_load_redirect(&$args)
    {
        $this->_request_data['query'] = new org_openpsa_reports_query_dba($args[0]);;

        if (empty($args[1])) {
            debug_add('Filename part not specified in URL, generating');
            //We do not have filename in URL, generate one and redirect
            $timestamp = $this->_request_data['query']->metadata->created;
            if (!$timestamp) {
                $timestamp = time();
            }
            $filename = date('Y_m_d', $timestamp);
            $title = $this->_request_data['query']->title ?: $this->module;
            $filename .= '_' . preg_replace('/[^a-z0-9-]/i', '_', strtolower($title));
            $filename .= $this->_request_data['query']->extension;

            return new midcom_response_relocate($this->module . '/' . $this->_request_data['query']->guid . '/' . $filename);
        }
        $this->_request_data['filename'] = $args[1];

        //Get DM schema data to array
        $dm = $this->load_datamanager($this->_request_data['query']);
        $this->_request_data['query_data'] = $dm->get_content_raw();
    }

    public function _handler_generator_style()
    {
        //Handle style
        if (empty($this->_request_data['query_data']['style'])) {
            debug_add('Empty style definition encountered, forcing builtin:basic');
            $this->_request_data['query_data']['style'] = 'builtin:basic';
        }
        if (!preg_match('/^builtin:(.+)/', $this->_request_data['query_data']['style'])) {
            debug_add("appending '{$this->_request_data['query_data']['style']}' to substyle path");
            midcom::get()->style->append_substyle($this->_request_data['query_data']['style']);
        }

        //TODO: Check if we're inside DL if so do not force mimetype
        if (empty($this->_request_data['query_data']['skip_html_headings'])) {
            //Skip normal style, and force content type based on query data.
            midcom::get()->skip_page_style = true;
            debug_add('Forcing content type: ' . $this->_request_data['query_data']['mimetype']);
            midcom::get()->header('Content-Type: ' . $this->_request_data['query_data']['mimetype']);
        }
    }

    /**
     * Convert midcom acl identifier to array of person ids
     */
    protected function _expand_resource($resource_id)
    {
        debug_add('Got resource_id: ' . $resource_id);
        $dba_obj = midcom::get()->auth->get_assignee($resource_id);
        $ret = [];

        if (is_object($dba_obj)) {
            switch (get_class($dba_obj)) {
                case midcom_core_group::class:
                    foreach ($dba_obj->list_members() as $core_user) {
                        $user_obj = $core_user->get_storage();
                        debug_add(sprintf('Adding user %s (id: %s)', $core_user->name, $user_obj->id));
                        $ret[] = $user_obj->id;
                    }
                    break;
                case midcom_core_user::class:
                    $user_obj = $dba_obj->get_storage();
                    debug_add(sprintf('Adding user %s (id: %s)', $dba_obj->name, $user_obj->id));
                    $ret[] = $user_obj->id;
                    break;
                default:
                    debug_add('Got unrecognized class for dba_obj: ' . get_class($dba_obj), MIDCOM_LOG_WARN);
                    break;
            }
        }
        return $ret;
    }
}
