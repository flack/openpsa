<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.net
 * @version $Id: admin.php 25716 2010-04-20 22:57:24Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.net
 * @license http://www.gnu.net/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * directmarketing edit/delete campaign handler
 *
 * Originally copied from net.nehmer.blog
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_admin extends midcom_baseclasses_components_handler
{
    /**
     * The campaign to operate on
     *
     * @var org_openpsa_directmarketing_campaign
     * @access private
     */
    var $_campaign = null;

    /**
     * The Datamanager of the campaign to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

    /**
     * The Controller of the campaign used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    var $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     * @access private
     */
    var $_schemadb = null;

    /**
     * Schema to use for campaign display
     *
     * @var string
     * @access private
     */
    var $_schema = null;

    /**
     * Simple default constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['campaign'] =& $this->_campaign;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    function _on_initialize()
    {
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
    }

    /**
     * Internal helper, loads the datamanager for the current campaign. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        if (!$this->_datamanager->autoset_storage($this->_campaign))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for campaign {$this->_campaign->id}.");
            // This will exit.
        }
    }

    /**
     * Internal helper, loads the controller for the current campaign. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_campaign);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for campaign {$this->_campaign->id}.");
            // This will exit.
        }
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $tmp = array();

        switch ($handler_id)
        {
            case 'edit_campaign':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "campaign/edit/{$this->_campaign->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('edit campaign'),
                );
                break;
            case 'delete_campaign':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "campaign/delete/{$this->_campaign->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('delete campaign'),
                );
                break;
            case 'edit_campaign_query':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "campaign/edit_query/{$this->_campaign->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('edit rules'),
                );
                break;
            case 'edit_campaign_query_advanced':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "campaign/edit_query/{$this->_campaign->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('edit rules'),
                );
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "campaign/edit_query_advanced/{$this->_campaign->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n->get('advanced rule editor'),
                );
                break;
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }


    private function _parse_row2rule_property($row, &$rule, &$from)
    {
        switch ($row['object'])
        {
            case 'person':
                $classname = 'org_openpsa_contacts_person_dba';
                break;
            case 'group':
                $classname = 'org_openpsa_contacts_group_dba';
                break;
            case 'membership':
                $classname = 'midgard_member';
                break;
            default:
                // Invalid object type, what to do ?
                return false;
                break;
        }
        $row_rule = array
        (
            'property'  => $row['property'],
            'match'     => $row['match'],
            'value'     => $row['value'],
        );
        $rule['classes'][] = array
        (
            'type'  => $rule['groups'],
            'class' => $classname,
            'rules' => array($row_rule),
        );
        return true;
    }

    private function _parse_row2rule_parameter_obj($row, &$rule, &$from)
    {
        static $param_count = 0;
        static $param_warning = false;
        $classname = 'midgard_parameter';
        if (!array_key_exists($classname, $rule['classes']))
        {
            $rule['classes'][$classname] = array
            (
                // PROBLEM: This cannot be AND or we never get any parameter results, but with OR we get too much results if the top level type is AND
                'type'   => 'OR',
                'class'  => $classname,
                'groups' => array(),
                'rules'  => array(),
            );
        }
        $classarray =& $rule['classes'][$classname];

        $group = array
        (
            'comment' => "\$object->parameter(\"{$row['parameter_domain']}\", \"{$row['parameter_name']}\") {$row['match']} \"{$row['value']}\"",
            'type'    => 'AND',
            'class'   => $classname,
            'rules'   => array
            (
                array
                (
                    'property'  => 'domain',
                    'match'     => '=',
                    'value'     => $row['parameter_domain'],
                ),
                array
                (
                    'property'  => 'name',
                    'match'     => '=',
                    'value'     => $row['parameter_name'],
                ),
                array
                (
                    'property'  => 'value',
                    'match'     => $row['match'],
                    'value'     => $row['value'],
                ),
            ),
        );
        $classarray['groups'][] = $group;
        $param_count++;
        if (   $param_count > 1
            && !$param_warning)
        {
            $param_warning = true;
            $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('rule generation warning') . ': ' . sprintf($this->_request_data['l10n']->get('with %s Midgard we can only support "%s" matching for multiple parameters'), mgd_version(), $this->_request_data['l10n']->get('match:any')), 'warning');
        }
        return true;
    }

    private function _parse_row2rule_parameter_dot($row, &$rule, &$from)
    {
        switch ($row['object'])
        {
            case 'person_parameters':
                $classname = 'org_openpsa_contacts_person_dba';
                break;
            case 'group_parameters':
                $classname = 'org_openpsa_contacts_group_dba';
                break;
            case 'membership_parameters':
                $classname = 'midgard_member';
                break;
            default:
                // Invalid object type, what to do ?
                return false;
                break;
        }
        if (!array_key_exists($classname, $rule['classes']))
        {
            $rule['classes'][$classname] = array
            (
                'type'  => $from['type'],
                'class' => $classname,
                'rules' => array(),
            );
        }
        $classarray =& $rule['classes'][$classname];
        if (!array_key_exists('groups', $classarray))
        {
            $classarray['groups'] = array();
        }
        $group = array
        (
            'comment' => "\$object->parameter(\"{$row['parameter_domain']}\", \"{$row['parameter_name']}\") {$row['match']} \"{$row['value']}\"",
            'type'    => 'AND',
            'class'   => $classname,
            'rules'   => array
            (
                array
                (
                    'property'  => 'parameter.domain',
                    'match'     => '=',
                    'value'     => $row['parameter_domain'],
                ),
                array
                (
                    'property'  => 'parameter.name',
                    'match'     => '=',
                    'value'     => $row['parameter_name'],
                ),
                array
                (
                    'property'  => 'parameter.value',
                    'match'     => $row['match'],
                    'value'     => $row['value'],
                ),
            ),
        );
        $classarray['groups'][] = $group;
        return true;
    }

    private function _parse_row2rule($row, &$rule, &$from)
    {
        static $parameter_count = 0;
        if (   !array_key_exists('match', $row)
            || !array_key_exists('value', $row))
        {
            // Invalid row, must have match and value
            return false;
        }
        if (   !array_key_exists('property', $row)
            && !(   array_key_exists('parameter_domain', $row)
                 && array_key_exists('parameter_name', $row)))
        {

            // Invalid row, must have either property or parameter domain and name
            return false;
        }
        if (strstr('LIKE', $row['match']) || strstr('NOT LIKE', $row['match']))
        {
            // convert tradiotional wildcard to SQL wildcard
            $row['value'] = str_replace('*', '%', $row['value']);
            // autowrap the LIKEs in editor ('contains' / '!contains') with wildcards if do not contain already
            if (!strstr('%', $row['value']))
            {
                $row['value'] = "%{$row['value']}%";
            }
        }
        if (array_key_exists('property', $row))
        {
            // Is property match
            return $this->_parse_row2rule_property($row, $rule, $from);
        }
        else if (   array_key_exists('parameter_domain', $row)
                 && array_key_exists('parameter_name', $row))
        {
            return $this->_parse_row2rule_parameter_obj($row, $rule, $from);
        }
        // We should never fall this far...
        return false;
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    function _handler_edit_query($handler_id, $args, &$data)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if (   !$this->_campaign
            || $this->_campaign->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[0]} was not found.");
            // This will exit.
        }

        $this->_campaign->require_do('midgard:update');

        $this->_prepare_request_data($handler_id);

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit_query_advanced/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('advanced rule editor'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $_MIDCOM->enable_jquery();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.js');
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css'
            )
        );
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/org.openpsa.core/list.css'
            )
        );

        // PONDER: Locking ?

        if (   isset($_POST['midcom_helper_datamanager2_cancel'])
            && !empty($_POST['midcom_helper_datamanager2_cancel']))
        {
            $_MIDCOM->relocate("campaign/{$this->_campaign->guid}/");
            // This will exit()
        }

        //check if it should be saved or preview
        if (   (isset($_POST['midcom_helper_datamanager2_save'])
            && !empty($_POST['midcom_helper_datamanager2_save']))
            || isset($_POST['show_rule_preview']))
        {
            $eval = '$tmp_array = ' . $_POST['midcom_helper_datamanager2_dummy_field_rules'] . ';';
            $eval_ret = @eval($eval);

            if (   $eval_ret === false
                || !is_array($tmp_array))
            {
                //Rule could not be parsed
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('given rule could not be parsed'), 'error');
                return true;
            }
            if (count($tmp_array) == 0)
            {
                // Rule array is empty
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('given rule is empty'), 'error');
                return true;
            }
            $rule = $tmp_array;
            //add rule was generated with wizard
            $rule['generated'] = 'wizard';

            //resolve rules
            $solver = new org_openpsa_directmarketing_campaign_ruleresolver();
            $rret = $solver->resolve($tmp_array);
            $rule_persons =  $solver->execute();


            //if it's not preview update campaign & Schedule background members refresh'
            if (!isset($_POST['show_rule_preview']))
            {
                $this->_campaign->rules = $rule;
                $update_ret = $this->_campaign->update();
                if (!$update_ret)
                {
                    //Save failed
                    $_MIDCOM->uimessages->add('org.openpsa.directmarketing', sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_application::get_error_string()), 'error');
                    break;
                }
                //Schedule background members refresh
                $this->_campaign->schedule_update_smart_campaign_members();

                //Save ok, relocate
                $_MIDCOM->relocate("campaign/{$this->_campaign->guid}/");
                //return true;
                // This will exit()
            }
            //set data for preview & skip page_style because of javascript call
            $data['preview_persons'] = $rule_persons;
            $_MIDCOM->skip_page_style = true;
        }

        $this->_component_data['active_leaf'] = "campaign_{$this->_campaign->id}";

        $_MIDCOM->set_pagetitle($this->_campaign->title);
        $_MIDCOM->bind_view_to_object($this->_campaign);
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }


    /**
     * Shows the loaded campaign.
     */
    function _show_edit_query($handler_id, &$data)
    {
        if (isset($_POST['show_rule_preview']))
        {
            midcom_show_style('show-campaign-preview');
        }
        else
        {
            midcom_show_style('show-campaign-edit_query');
        }
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    function _handler_edit_query_advanced($handler_id, $args, &$data)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if (   !$this->_campaign
            || $this->_campaign->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[0]} was not found.");
            // This will exit.
        }

        $this->_campaign->require_do('midgard:update');

        $this->_prepare_request_data($handler_id);

        if (   isset($_POST['midcom_helper_datamanager2_cancel'])
            && !empty($_POST['midcom_helper_datamanager2_cancel']))
        {
            $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "campaign/" . $this->_request_data["campaign"]->guid . '/');
            // This will exit()
        }

         $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css'
            )
        );

        if (   isset($_POST['midcom_helper_datamanager2_save'])
            && !empty($_POST['midcom_helper_datamanager2_save']))
        {
            //Actual save routine
            if (   !isset($_POST['midcom_helper_datamanager2_dummy_field_rules'])
                || empty($_POST['midcom_helper_datamanager2_dummy_field_rules']))
            {
                //Rule code empty
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('no rule given'), 'error');
                return true;
            }
            $eval = '$tmp_array = ' . $_POST['midcom_helper_datamanager2_dummy_field_rules'] . ';';
            $eval_ret = @eval($eval);
            if (   $eval_ret === false
                || !is_array($tmp_array))
            {
                //Rule could not be parsed
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('given rule could not be parsed'), 'error');
                return true;
            }
            if (count($tmp_array) == 0)
            {
                // Rule array is empty
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('given rule is empty'), 'error');
                return true;
            }
            if (array_key_exists('generated_from',  $tmp_array))
            {
                debug_add('"generated_from" found in advanced rule, removing', MIDCOM_LOG_WARN);
                unset ($tmp_array['generated_from']);
                // PONDER: return to editor or save anyway ? now we overwrite the value with the modified rule and return to editor.
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('longtext:generated_from_found_in_adv_rule'), 'error');
                $_POST['midcom_helper_datamanager2_dummy_field_rules'] = org_openpsa_helpers::array2code($tmp_array);

                $this->_update_breadcrumb_line($handler_id);
                org_openpsa_helpers::dm2_savecancel($this);
                return true;
            }
            $this->_request_data['campaign']->rules = $tmp_array;
            $update_ret = $this->_request_data['campaign']->update();
            if (!$update_ret)
            {
                //Save failed
                $_MIDCOM->uimessages->add('org.openpsa.directmarketing', sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_application::get_error_string()), 'error');
                return true;
            }

            //Schedule background members refresh
            $this->_request_data['campaign']->schedule_update_smart_campaign_members();

            //Save ok, relocate
            $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "campaign/" . $this->_request_data["campaign"]->guid . '/');
            // This will exit()
        }

        $this->_component_data['active_leaf'] = "campaign_{$this->_campaign->id}";

        $_MIDCOM->set_pagetitle($this->_campaign->title);

        org_openpsa_helpers::dm2_savecancel($this);
        $_MIDCOM->bind_view_to_object($this->_campaign);

        $this->_update_breadcrumb_line($handler_id);

        return true;
    }


    /**
     * Shows the loaded campaign.
     */
    function _show_edit_query_advanced($handler_id, &$data)
    {
        midcom_show_style('show-campaign-edit_query-advanced');
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if (   !$this->_campaign
            || $this->_campaign->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[0]} was not found.");
            // This will exit.
        }

        $this->_campaign->require_do('midgard:update');
        $this->_component_data['active_leaf'] = "campaign_{$this->_campaign->id}";

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the campaign
                //$indexer = $_MIDCOM->get_service('indexer');
                //org_openpsa_directmarketing_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                $_MIDCOM->relocate("campaign/{$this->_campaign->guid}/");
                // This will exit.
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/delete/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete campaign'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:delete'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            )
        );

        $this->_prepare_request_data($handler_id);
        $_MIDCOM->set_pagetitle($this->_campaign->title);
        $_MIDCOM->bind_view_to_object($this->_campaign, $this->_request_data['controller']->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }


    /**
     * Shows the loaded campaign.
     */
    function _show_edit ($handler_id, &$data)
    {
        midcom_show_style('show-campaign-edit');
    }

    /**
     * Displays an campaign delete confirmation view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    function _handler_delete($handler_id, $args, &$data)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if (   !$this->_campaign
            || $this->_campaign->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[0]} was not found.");
            // This will exit.
        }

        $this->_campaign->require_do('midgard:delete');

        $this->_load_datamanager();

        if (array_key_exists('org_openpsa_directmarketing_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (! $this->_campaign->delete())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to delete campaign {$args[0]}, last Midgard error was: " . midcom_application::get_error_string());
                // This will exit.
            }

            // Update the index
            $indexer = $_MIDCOM->get_service('indexer');
            $indexer->delete($this->_campaign->guid);

            // Delete ok, relocating to welcome.
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('org_openpsa_directmarketing_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            $_MIDCOM->relocate("campaign/{$this->_campaign->guid}/");
            // This will exit()
        }

        $this->_component_data['active_leaf'] = "campaign_{$this->_campaign->id}";

        $this->_prepare_request_data($handler_id);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit campaign'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        $_MIDCOM->set_pagetitle($this->_campaign->title);
        $_MIDCOM->bind_view_to_object($this->_campaign, $this->_datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }


    /**
     * Shows the loaded campaign.
     */
    function _show_delete ($handler_id, &$data)
    {
        $data['view_campaign'] = $this->_datamanager->get_content_html();

        midcom_show_style('show-campaign-delete');
    }
}

?>