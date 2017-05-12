<?php
/**
 * @package org.openpsa.relatedto
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * journal entry list handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_journal_list extends midcom_baseclasses_components_handler
{
    /**
     * @var midcom_core_querybuilder
     */
    private $qb;

    /**
     * @var midcom_core_dbaobject
     */
    private $object;

    private $object_url;

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.relatedto');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_object($handler_id, array $args, array &$data)
    {
        $this->object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        $this->object_url = midcom::get()->permalinks->create_permalink($this->object->guid);
        $data['url_prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";

        $this->qb = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb->add_constraint('linkGuid', '=', $args[0]);
        $this->qb->add_order('followUp', 'DESC');
        $data['entries'] = $this->qb->execute();

        $this->_prepare_output();
        org_openpsa_widgets_grid::add_head_elements();

        //prepare breadcrumb
        if ($this->object_url) {
            $ref = midcom_helper_reflector::get($this->object);
            $this->add_breadcrumb($this->object_url, $ref->get_object_label($this->object));
        }
        $this->add_breadcrumb(
            midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/render/" . $this->object->guid . "/both/",
            $this->_l10n->get('view related information')
        );
        $this->add_breadcrumb("", $this->_l10n->get('journal entries'));
    }

    /**
     * function to add css & toolbar-items
     */
    private function _prepare_output()
    {
        $workflow = $this->get_workflow('datamanager2');
        $buttons = array(
            array(
                MIDCOM_TOOLBAR_URL => $this->object_url,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            ),
            $workflow->get_button($this->_request_data['url_prefix'] . "create/" . $this->object->guid . "/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('add journal entry'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png'
            ))
        );
        $this->_view_toolbar->add_items($buttons);

        org_openpsa_widgets_contact::add_head_elements();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_object($handler_id, array &$data)
    {
        midcom_show_style('show_entries_html');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        //set the start-constraints for journal-entries
        $time_span = 7 * 24 * 60 * 60; //7 days

        $data['journal_constraints'] = array(
            //just show entries of current_user
            array(
                'property' => 'metadata.creator',
                'operator' => '=',
                'value' => midcom::get()->auth->user->guid,
            ),
            //only show entries with followUp set and within the next 7 days
            array(
                'property' => 'followUp',
                'operator' => '<',
                'value' => $args[0] + $time_span,
            ),
            array(
                'property' => 'followUp',
                'operator' => '>',
                'value' => 0,
            ),
            array(
                'property' => 'closed',
                'operator' => '=',
                'value' => false,
            )
        );
        $data['url_prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";
        org_openpsa_widgets_grid::add_head_elements();
        midcom::get()->head->set_pagetitle($this->_l10n->get('journal entries'));
        $this->add_breadcrumb('', $this->_l10n->get('journal entries'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('show_entries_list');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_xml($handler_id, array $args, array &$data)
    {
        $this->qb = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb->add_order('followUp');
        $this->_prepare_journal_query();

        //show the corresponding object of the entry
        $data['show_object'] = true;
        $data['show_closed'] = array_key_exists('show_closed', $_POST);
        $data['page'] = 1;

        $data['entries'] = $this->qb->execute();

        //get the corresponding objects
        if (!empty($data['entries'])) {
            $data['linked_objects'] = array();
            $data['linked_raw_objects'] = array();

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
        }
        //url_prefix to build the links to the entries
        $data['url_prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";
        midcom::get()->header("Content-type: text/xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_xml($handler_id, array &$data)
    {
        midcom_show_style('show_entries_xml');
    }

    private function _prepare_journal_query()
    {
        if (array_key_exists('journal_entry_constraints', $_POST)) {
            foreach ($_POST['journal_entry_constraints'] as $constraint) {
                //"type-cast" for closed because it will be passed as string
                if ($constraint['property'] == 'closed') {
                    $constraint['value'] = ($constraint['value'] != 'false');
                }
                $this->qb->add_constraint($constraint['property'], $constraint['operator'], $constraint['value']);
            }
        }
        //check if there is a page & rows - parameter passed - if add them to qb
        if (array_key_exists('page', $_POST) && array_key_exists('rows', $_POST)) {
            $this->_request_data['page'] = $_POST['page'];
            $this->qb->set_limit((int)$_POST['rows']);
            $offset = ((int)$_POST['page'] - 1) * (int)$_POST['rows'];
            $this->qb->set_offset($offset);
        }
    }
}
