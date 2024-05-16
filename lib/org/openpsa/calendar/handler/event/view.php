<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.calendar site interface class.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_event_view extends midcom_baseclasses_components_handler
{
    private datamanager $datamanager;

    /**
     * Handle the single event view
     */
    public function _handler_event(Request $request, string $handler_id, string $guid, array &$data)
    {
        // Get the requested event object
        $data['event'] = new org_openpsa_calendar_event_dba($guid);

        midcom::get()->skip_page_style = ($handler_id == 'event_view_raw');

        $this->load_datamanager();

        // Add toolbar items
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('event_edit', ['guid' => $guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_GLYPHICON => 'pencil',
                MIDCOM_TOOLBAR_ENABLED => $data['event']->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]
        ];
        if ($data['event']->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $data['event'], 'relocate' => false]);
            $buttons[] = $workflow->get_button($this->router->generate('event_delete', ['guid' => $guid]));
        }
        $buttons[] = [
            MIDCOM_TOOLBAR_URL => 'javascript:window.print()',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('print'),
            MIDCOM_TOOLBAR_GLYPHICON => 'print',
            MIDCOM_TOOLBAR_OPTIONS => ['rel' => 'directlink']
        ];

        if ($user = midcom::get()->auth->user?->get_storage()) {
            $date = $this->_l10n->get_formatter()->date();
            $relatedto_button_settings = [
                'wikinote' => [
                    'component' => 'net.nemein.wiki',
                    'node' => false,
                    'wikiword' => str_replace('/', '-', sprintf($this->_l10n->get($this->_config->get('wiki_title_skeleton')), $data['event']->title, $date, $user->name)),
                ],
            ];
            org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $data['event'], $this->_component, $relatedto_button_settings);
        }
        $this->_view_toolbar->add_items($buttons);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('event %s'), $data['event']->title));
        return $this->get_workflow('viewer')->run($request);
    }

    private function load_datamanager()
    {
        // Load schema database
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        $schema = null;
        if (!$this->_request_data['event']->can_do('org.openpsa.calendar:read')) {
            $schema = 'private';
        }
        $this->datamanager = new datamanager($schemadb);
        $this->datamanager->set_storage($this->_request_data['event'], $schema);
    }

    /**
     * Show a single event
     */
    public function _show_event(string $handler_id, array &$data)
    {
        if ($handler_id == 'event_view') {
            // Show popup
            $data['event_dm'] = $this->datamanager;
            midcom_show_style('show-event');
        } else {
            midcom_show_style('show-event-raw');
        }
    }
}
