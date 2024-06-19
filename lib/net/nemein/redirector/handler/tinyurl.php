<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;
use midcom\datamanager\controller;

/**
 * @package net.nemein.redirector
 */
class net_nemein_redirector_handler_tinyurl extends midcom_baseclasses_components_handler
{
    private net_nemein_redirector_tinyurl_dba $_tinyurl;

    private function load_controller() : controller
    {
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_tinyurl'));
        $dm->set_storage($this->_tinyurl);
        return $dm->get_controller();
    }

    /**
     * Populate request data
     */
    private function _populate_request_data(string $handler_id)
    {
        if ($handler_id === 'edit') {
            $this->add_breadcrumb($this->router->generate('tinyurl', ['tinyurl' => $this->_tinyurl->name]), $this->_tinyurl->title);
            $this->add_breadcrumb($this->router->generate('edit', ['tinyurl' => $this->_tinyurl->name]), $this->_l10n_midcom->get('edit'));
            $workflow = $this->get_workflow('delete', ['object' => $this->_tinyurl]);
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('delete', ['tinyurl' => $this->_tinyurl->guid])));
            $this->_view_toolbar->bind_to($this->_tinyurl);
        } elseif ($handler_id === 'create') {
            $this->add_breadcrumb($this->router->generate('create'), sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('tinyurl')));
        }
    }

    /**
     * Get the item according to the given rule
     */
    private function _get_item(string $rule) : net_nemein_redirector_tinyurl_dba
    {
        $qb = net_nemein_redirector_tinyurl_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->guid);

        $qb->begin_group('OR');
        $qb->add_constraint('guid', '=', $rule);
        $qb->add_constraint('name', '=', $rule);
        $qb->end_group();
        $results = $qb->execute();

        if (empty($results)) {
            throw new midcom_error_notfound('Item not found');
        }

        return $results[0];
    }

    /**
     * Create a new TinyURL
     */
    public function _handler_create(Request $request, string $handler_id, array &$data)
    {
        $this->_topic->require_do('midgard:create');

        $this->_tinyurl = new net_nemein_redirector_tinyurl_dba();
        $this->_tinyurl->node = $this->_topic->guid;

        // Load the controller
        $data['controller'] = $this->load_controller();

        if ($data['controller']->handle($request) == 'save') {
            return new midcom_response_relocate("edit/{$this->_tinyurl->name}");
        }

        // Set the request data
        $this->_populate_request_data($handler_id);
        return $this->show('tinyurl-create');
    }

    /**
     * Edit an existing TinyURL
     */
    public function _handler_edit(Request $request, string $handler_id, string $tinyurl, array &$data)
    {
        $this->_tinyurl = $this->_get_item($tinyurl);
        $this->_tinyurl->require_do('midgard:update');

        // Edit controller
        $data['controller'] = $this->load_controller();
        $data['tinyurl'] = $this->_tinyurl;

        switch ($data['controller']->handle($request)) {
            case 'save':
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n_midcom->get('saved'));
                // Fall through

            case 'cancel':
                return new midcom_response_relocate('');
        }

        // Set the request data
        $this->_populate_request_data($handler_id);
        return $this->show('tinyurl-edit');
    }

    /**
     * Delete an existing TinyURL
     */
    public function _handler_delete(Request $request, string $tinyurl)
    {
        $this->_tinyurl = $this->_get_item($tinyurl);
        $workflow = $this->get_workflow('delete', ['object' => $this->_tinyurl]);
        return $workflow->run($request);
    }
}
