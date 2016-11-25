<?php
/**
 * @package org.openpsa.products
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_index extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    /**
     * The grid provider
     *
     * @var org_openpsa_widgets_grid_provider
     */
    private $provider;

    public function _on_initialize()
    {
        $this->provider = new org_openpsa_widgets_grid_provider($this);
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = array())
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();

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

        return array(
            'id' => $object->id,
            'index_code' => $object->code,
            'code' => $link_html . $object->code . '</a>',
            'index_title' => $object->title,
            'title' => $link_html . $object->title . '</a>',
            'price' => $object->price,
            'delivery' => $object->delivery,
            'orgOpenpsaObtype' => $object->orgOpenpsaObtype,
            'unit' => $object->unit
        );
    }

    /**
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
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
    public function _show_index($handler_id, array &$data)
    {
        midcom_show_style('index');
    }

    private function _populate_toolbar()
    {
        $allow_create_group = midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_products_product_group_dba');
        $allow_create_product = midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_products_product_dba');

        $this->_add_schema_buttons('schemadb_group', 'new-dir', '', $allow_create_group);
        $this->_add_schema_buttons('schemadb_product', 'new-text', 'product/', $allow_create_product);
    }

    private function _add_schema_buttons($schemadb_name, $default_icon, $prefix, $allowed)
    {
        $workflow = $this->get_workflow('datamanager2');
        foreach (array_keys($this->_request_data[$schemadb_name]) as $name) {
            $config = array(
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . $default_icon . '.png',
                MIDCOM_TOOLBAR_ENABLED => $allowed,
                MIDCOM_TOOLBAR_LABEL => sprintf(
                    $this->_l10n_midcom->get('create %s'),
                    $this->_l10n->get($this->_request_data[$schemadb_name][$name]->description)
                ),
            );
            if (isset($this->_request_data[$schemadb_name][$name]->customdata['icon'])) {
                $config[MIDCOM_TOOLBAR_ICON] = $this->_request_data[$schemadb_name][$name]->customdata['icon'];
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
        midcom::get()->skip_page_style = true;
        $data['provider'] = $this->provider;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_json($handler_id, array &$data)
    {
        midcom_show_style('index-json');
    }
}
