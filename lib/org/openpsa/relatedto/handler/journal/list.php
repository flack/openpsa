<?php
/**
 * @package org.openpsa.relatedto
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\grid\grid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * journal entry list handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_journal_list extends midcom_baseclasses_components_handler
{
    private midcom_core_querybuilder $qb;

    private midcom_core_dbaobject $object;

    private $object_url;

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.relatedto');
    }

    public function _handler_object(string $guid, array &$data)
    {
        $this->object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $this->object_url = midcom::get()->permalinks->create_permalink($this->object->guid);

        $this->qb = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb->add_constraint('linkGuid', '=', $guid);
        $this->qb->add_order('followUp', 'DESC');
        $data['entries'] = $this->qb->execute();

        $this->_prepare_output();
        grid::add_head_elements();

        //prepare breadcrumb
        if ($this->object_url) {
            $ref = midcom_helper_reflector::get($this->object);
            $this->add_breadcrumb($this->object_url, $ref->get_object_label($this->object));
        }
        $this->add_breadcrumb(
            $this->router->generate('render', ['guid' => $guid, 'mode' => 'both']),
            $this->_l10n->get('view related information')
        );
        $this->add_breadcrumb("", $this->_l10n->get('journal entries'));

        return $this->show('show_entries_html');
    }

    /**
     * function to add css & toolbar-items
     */
    private function _prepare_output()
    {
        $workflow = $this->get_workflow('datamanager');
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => $this->object_url,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                MIDCOM_TOOLBAR_GLYPHICON => 'eject',
            ],
            $workflow->get_button($this->router->generate('journal_entry_create', ['guid' => $this->object->guid]), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('add journal entry'),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus'
            ])
        ];
        $this->_view_toolbar->add_items($buttons);
    }

    public function _handler_list(int $time, array &$data)
    {
        //set the start-constraints for journal-entries
        $time_span = 7 * 24 * 60 * 60; //7 days

        $data['journal_constraints'] = [
            'journal_entry_constraints' => [
                //just show entries of current_user
                [
                    'property' => 'metadata.creator',
                    'operator' => '=',
                    'value' => midcom::get()->auth->user->guid,
                ],
                //only show entries with followUp set and within the next 7 days
                [
                    'property' => 'followUp',
                    'operator' => '<',
                    'value' => $time + $time_span,
                ],
                [
                    'property' => 'followUp',
                    'operator' => '>',
                    'value' => 0,
                ],
                [
                    'property' => 'closed',
                    'operator' => '=',
                    'value' => false,
                ]
            ]
        ];
        midcom::get()->head->set_pagetitle($this->_l10n->get('journal entries'));
        $this->add_breadcrumb('', $this->_l10n->get('journal entries'));

        return $this->show('show_entries_list');
    }

    public function _handler_xml(Request $request, array &$data)
    {
        $this->qb = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb->add_order('followUp');
        $this->_prepare_journal_query($request->request);

        //show the corresponding object of the entry
        $data['show_object'] = true;
        $data['show_closed'] = $request->request->getBoolean('show_closed');
        $data['page'] = 1;

        $data['entries'] = $this->qb->execute();

        //get the corresponding objects
        $data['linked_objects'] = [];
        $data['linked_raw_objects'] = [];

        foreach ($data['entries'] as $i => $entry) {
            if (array_key_exists($entry->linkGuid, $data['linked_objects'])) {
                continue;
            }
            //create reflector with linked object to get the right label
            try {
                $linked_object = midcom::get()->dbfactory->get_object_by_guid($entry->linkGuid);
            } catch (midcom_error $e) {
                unset($data['entries'][$i]);
                $e->log();
                continue;
            }

            $reflector = midcom_helper_reflector::get($linked_object);
            $link_html = "<a href='" . midcom::get()->permalinks->create_permalink($linked_object->guid) . "'>" . $reflector->get_object_label($linked_object) ."</a>";
            $data['linked_objects'][$entry->linkGuid] = $link_html;
            $data['linked_raw_objects'][$entry->linkGuid] = $reflector->get_object_label($linked_object);
        }
        midcom::get()->skip_page_style = true;
        $response = $this->show('show_entries_xml');
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        return $response;
    }

    private function _prepare_journal_query(ParameterBag $post)
    {
        if ($constraints = $post->get('journal_entry_constraints')) {
            foreach ($constraints as $constraint) {
                //"type-cast" for closed because it will be passed as string
                if ($constraint['property'] == 'closed') {
                    $constraint['value'] = ($constraint['value'] != 'false');
                }
                $this->qb->add_constraint($constraint['property'], $constraint['operator'], $constraint['value']);
            }
        }
        //check if there is a page & rows - parameter passed - if add them to qb
        if ($post->has('page') && $post->has('rows')) {
            $this->_request_data['page'] = $post->get('page');
            $this->qb->set_limit($post->getInt('rows'));
            $offset = ($post->getInt('page') - 1) * $post->getInt('rows');
            $this->qb->set_offset($offset);
        }
    }
}
