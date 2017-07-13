<?php
/**
 * @package org.openpsa.products
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_list extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    /**
     * The grid provider
     *
     * @var org_openpsa_widgets_grid_provider
     */
    private $provider;

    /**
     * @var datamanager
     */
    private $datamanager;

    public function _on_initialize()
    {
        $this->provider = new org_openpsa_widgets_grid_provider($this);
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = [])
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();

        if (!empty($this->_request_data['group'])) {
            if ($this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
                // Smart group, query products by stored constraints
                $constraints = $this->_request_data['group']->list_parameters('org.openpsa.products:constraints');
                if (empty($constraints)) {
                    $qb->add_constraint('productGroup', '=', $this->_request_data['group']->id);
                }

                $reflector = new midgard_reflection_property('org_openpsa_products_product');

                foreach ($constraints as $constraint_string) {
                    $constraint_members = explode(',', $constraint_string);
                    if (count($constraint_members) != 3) {
                        throw new midcom_error("Invalid constraint '{$constraint_string}'");
                    }

                    // Reflection is needed here for safety
                    $field_type = $reflector->get_midgard_type($constraint_members[0]);
                    switch ($field_type) {
                        case 4:
                            throw new midcom_error("Invalid constraint: '{$constraint_members[0]}' is not a Midgard property");
                        case MGD_TYPE_INT:
                            $constraint_members[2] = (int) $constraint_members[2];
                            break;
                        case MGD_TYPE_FLOAT:
                            $constraint_members[2] = (float) $constraint_members[2];
                            break;
                        case MGD_TYPE_BOOLEAN:
                            $constraint_members[2] = (boolean) $constraint_members[2];
                            break;
                    }
                    $qb->add_constraint($constraint_members[0], $constraint_members[1], $constraint_members[2]);
                }
            } else {
                $qb->add_constraint('productGroup', '=', $this->_request_data['group']->id);
            }
        }

        if ($field !== null) {
            $qb->add_order($field, $direction);
        }

        foreach ($search as $key => $value) {
            if ($key === 'code' || $key === 'title') {
                $qb->add_constraint($key, 'LIKE', $value . '%');
            } else {
                $qb->add_constraint($key, '=', $value);
            }
        }

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $object)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $link_html = "<a href='{$prefix}product/{$object->guid}/'>";

        return [
            'id' => $object->id,
            'index_code' => $object->code,
            'code' => $link_html . $object->code . '</a>',
            'index_title' => $object->title,
            'title' => $link_html . $object->title . '</a>',
            'price' => $object->price,
            'delivery' => $object->delivery,
            'orgOpenpsaObtype' => $object->orgOpenpsaObtype,
            'unit' => $object->unit
        ];
    }

    /**
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $data['data_url'] = 'json/';
        if ($handler_id === 'list_group') {
            $data['group'] = new org_openpsa_products_product_group_dba($args[0]);
            $data['data_url'] .= $data['group']->guid . '/';

            $this->datamanager = new datamanager($data['schemadb_group']);
            $this->datamanager->set_storage($data['group']);
            $tmp = $this->_master->update_breadcrumb_line($this->_request_data['group']);
            midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $tmp);
        }
        $data['grid'] = $this->provider->get_grid('product_search');
        $data['view_title'] = $this->_l10n->get('product database');

        $this->_populate_toolbar();
        midcom::get()->head->set_pagetitle($data['view_title']);
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        if ($this->datamanager) {
            $data['view_group'] = $this->datamanager->get_content_html();
        }

        midcom_show_style('list');
    }

    private function _populate_toolbar()
    {
        $allow_create_group = midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_products_product_group_dba');
        $allow_create_product = midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_products_product_dba');

        if (!empty($this->_request_data['group'])) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button("edit/{$this->_request_data['group']->guid}/", [
                MIDCOM_TOOLBAR_ENABLED => $this->_request_data['group']->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]));
            if ($this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
                $allow_create_product = false;
            }
        }

        $this->_add_schema_buttons('schemadb_group', 'new-dir', '', $allow_create_group);
        $this->_add_schema_buttons('schemadb_product', 'new-text', 'product/', $allow_create_product);

        if (!empty($this->_request_data['group'])) {
            $this->bind_view_to_object($this->_request_data['group'], $this->datamanager->get_schema()->get_name());
        }
    }

    private function _add_schema_buttons($schemadb_name, $default_icon, $prefix, $allowed)
    {
        $workflow = $this->get_workflow('datamanager');
        foreach ($this->_request_data[$schemadb_name]->all() as $name => $schema) {
            $config = [
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . $default_icon . '.png',
                MIDCOM_TOOLBAR_ENABLED => $allowed,
                MIDCOM_TOOLBAR_LABEL => sprintf(
                    $this->_l10n_midcom->get('create %s'),
                    $this->_l10n->get($schema->get('description'))
                ),
            ];
            if (isset($schema->get('customdata')['icon'])) {
                $config[MIDCOM_TOOLBAR_ICON] = $schema->get('customdata')['icon'];
            }
            $create_url = 'create/0/' . $name . '/';
            $this->_view_toolbar->add_item($workflow->get_button($prefix . $create_url, $config));
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_json($handler_id, array $args, array &$data)
    {
        if ($handler_id === 'list_json_group') {
            $data['group'] = new org_openpsa_products_product_group_dba($args[0]);
        }
        midcom::get()->skip_page_style = true;
        $data['provider'] = $this->provider;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_json($handler_id, array &$data)
    {
        midcom_show_style('list-json');
    }
}
