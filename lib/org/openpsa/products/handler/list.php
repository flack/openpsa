<?php
/**
 * @package org.openpsa.products
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_list extends midcom_baseclasses_components_handler
implements client
{
    use org_openpsa_products_handler;

    /**
     * The grid provider
     *
     * @var provider
     */
    private $provider;

    /**
     * @var datamanager
     */
    private $datamanager;

    public function _on_initialize()
    {
        $this->provider = new provider($this);
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
                        case MGD_TYPE_NONE:
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
        $link_html = '<a href="' . $this->router->generate('view_product', ['guid' => $object->guid]) . '">';

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
     * @param array $data The local request data.
     * @param string $guid The object's GUID
     */
    public function _handler_list(array &$data, $guid = null)
    {
        $data['data_url'] = 'json/';
        if ($guid !== null) {
            $data['group'] = new org_openpsa_products_product_group_dba($guid);
            $data['data_url'] .= $guid . '/';

            $this->datamanager = new datamanager($data['schemadb_group']);
            $this->datamanager->set_storage($data['group']);
            $tmp = $this->update_breadcrumb_line($data['group']);
            midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $tmp);
        }
        $data['grid'] = $this->provider->get_grid('product_search');
        $data['view_title'] = $this->_l10n->get('product database');

        $this->_populate_toolbar();
        midcom::get()->head->set_pagetitle($data['view_title']);
        if ($this->datamanager) {
            $data['view_group'] = $this->datamanager->get_content_html();
        }

        return $this->show('list');
    }

    private function _populate_toolbar()
    {
        $allow_create_group = midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_products_product_group_dba::class);
        $allow_create_product = midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_products_product_dba::class);

        if (!empty($this->_request_data['group'])) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('edit_product_group', [
                'guid' => $this->_request_data['group']->guid
            ]), [
                MIDCOM_TOOLBAR_ENABLED => $this->_request_data['group']->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]));
            if ($this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
                $allow_create_product = false;
            }
        }

        $this->_add_schema_buttons('schemadb_group', 'cubes', '', $allow_create_group);
        $this->_add_schema_buttons('schemadb_product', 'cube', 'product/', $allow_create_product);

        if (!empty($this->_request_data['group'])) {
            $this->bind_view_to_object($this->_request_data['group'], $this->datamanager->get_schema()->get_name());
        }
    }

    private function _add_schema_buttons($schemadb_name, $default_icon, $prefix, $allowed)
    {
        $workflow = $this->get_workflow('datamanager');
        foreach ($this->_request_data[$schemadb_name]->all() as $name => $schema) {
            $config = [
                MIDCOM_TOOLBAR_GLYPHICON => $default_icon,
                MIDCOM_TOOLBAR_ENABLED => $allowed,
                MIDCOM_TOOLBAR_LABEL => sprintf(
                    $this->_l10n_midcom->get('create %s'),
                    $this->_l10n->get($schema->get('description'))
                ),
            ];
            if (isset($schema->get('customdata')['icon'])) {
                $config[MIDCOM_TOOLBAR_ICON] = $schema->get('customdata')['icon'];
            }
            $create_url = 'create/' . (!empty($this->_request_data['group']) ? $this->_request_data['group']->id : '0') . '/' . $name . '/';
            $this->_view_toolbar->add_item($workflow->get_button($prefix . $create_url, $config));
        }
    }

    /**
     * @param array $data The local request data.
     * @param string $guid The object's GUID
     */
    public function _handler_json(array &$data, $guid = null)
    {
        if ($guid !== null) {
            $data['group'] = new org_openpsa_products_product_group_dba($guid);
        }
        midcom::get()->skip_page_style = true;
        $data['provider'] = $this->provider;

        return $this->show('list-json');
    }
}
