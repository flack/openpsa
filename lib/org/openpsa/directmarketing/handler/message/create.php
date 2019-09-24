<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Direct marketing page handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_create extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message;

    /**
     * Displays an message create view.
     */
    public function _handler_create(Request $request, string $campaign, string $schema, array &$data)
    {
        $data['campaign'] = $this->load_campaign($campaign);
        $data['campaign']->require_do('midgard:create');

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_message'));
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba();
        $this->_message->campaign = $data['campaign']->id;
        $dm->set_storage($this->_message, $schema);
        $this->_message->orgOpenpsaObtype = $dm->get_schema()->get('customdata')['org_openpsa_directmarketing_messagetype'];

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($dm->get_schema()->get('description'))));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $dm->get_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        return $this->router->generate('message_view', ['guid' => $this->_message->guid]);
    }
}
