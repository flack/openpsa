<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Permissions interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_permissions extends midcom_baseclasses_components_handler
{
    /**
     * The object whose permissions we handle
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * The Controller of the object used for editing
     *
     * @var controller
     */
    private $_controller;

    /**
     * Privileges we're managing here
     *
     * @var Array
     */
    private $_privileges = [
        // Midgard core level privileges
        'midgard:owner', 'midgard:read', 'midgard:update', 'midgard:delete', 'midgard:create',
        'midgard:parameters', 'midgard:attachments', 'midgard:privileges'
    ];

    /**
     * Table header
     *
     * @var String
     */
    private $_header = '';

    /**
     * Available row labels
     *
     * @var Array
     */
    private $_row_labels = [];

    private $additional_assignee;

    public function _on_initialize()
    {
        if (midcom::get()->config->get('metadata_approval')) {
            $this->_privileges[] = 'midcom:approve';
        }

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/permissions/permissions.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/permissions/layout.css');
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['object'] = $this->_object;
        $this->_request_data['controller'] = $this->_controller;
    }

    /**
     * Load component-defined additional privileges
     */
    private function _load_component_privileges()
    {
        $component_loader = midcom::get()->componentloader;

        // Store temporarily the requested object
        $tmp = $this->_object;

        $i = 0;
        while (   !empty($tmp->guid)
               && !is_a($tmp, midcom_db_topic::class)
               && $i < 100) {
            // Get the parent; wishing eventually to get a topic
            $tmp = $tmp->get_parent();
            $i++;
        }

        // If the temporary object eventually reached a topic, fetch its manifest
        if (is_a($tmp, midcom_db_topic::class)) {
            $current_manifest = $component_loader->manifests[$tmp->component];
        } else {
            $current_manifest = $component_loader->manifests[midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT)];
        }
        $this->_privileges = array_merge($this->_privileges, array_keys($current_manifest->privileges));

        if (!empty($current_manifest->customdata['midgard.admin.asgard.acl']['extra_privileges'])) {
            foreach ($current_manifest->customdata['midgard.admin.asgard.acl']['extra_privileges'] as $privilege) {
                if (!strpos($privilege, ':')) {
                    // Only component specified
                    // TODO: load components manifest and add privileges from there
                    continue;
                }
                $this->_privileges[] = $privilege;
            }
        }

        // In addition, give component configuration privileges if we're in topic
        if ($this->_object instanceof midcom_db_topic) {
            $this->_privileges[] = 'midcom.admin.folder:topic_management';
            $this->_privileges[] = 'midcom.admin.folder:template_management';
            $this->_privileges[] = 'midcom:component_config';
            $this->_privileges[] = 'midcom:urlname';
        }
    }

    /**
     * Generates, loads and prepares the schema database.
     *
     * @return controller
     */
    private function load_controller()
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_permissions'));

        $assignees = $this->load_assignees();
        $this->process_assignees($assignees, $schemadb);
        $assignee_field =& $schemadb->get('privileges')->get_field('add_assignee');

        if (!$this->additional_assignee) {
            // Populate additional assignee selector
            $additional_assignees = [
                '' => '',
                'EVERYONE' => $this->_l10n->get('EVERYONE'),
                'USERS' => $this->_l10n->get('USERS'),
                'ANONYMOUS' => $this->_l10n->get('ANONYMOUS')
            ];

            // List groups as potential assignees
            $qb = midcom_db_group::new_query_builder();
            foreach ($qb->execute() as $group) {
                if (!array_key_exists("group:{$group->guid}", $assignees)) {
                    $additional_assignees["group:{$group->guid}"] = $group->get_label();
                }
            }
            asort($additional_assignees);

            // Add the 'Add assignees' choices to schema
            $assignee_field['type_config']['options'] = $additional_assignees;
        } else {
            $assignee_field['type'] = 'text';
            $assignee_field['widget'] = 'hidden';
        }
        $dm = new datamanager($schemadb);

        return $dm
            ->set_storage($this->_object)
            ->get_controller();
    }

    private function process_assignees(array $assignees, schemadb $schemadb)
    {
        $header = '';
        $header_items = [];
        $fields = $schemadb->get('privileges')->get('fields');

        foreach ($assignees as $assignee => $label) {
            foreach ($this->_privileges as $privilege) {
                $privilege_components = explode(':', $privilege);
                if (   $privilege_components[0] == 'midcom'
                    || $privilege_components[0] == 'midgard') {
                    // This is one of the core privileges, we handle it
                    $privilege_label = $privilege;
                } else {
                    // This is a component-specific privilege, call component to localize it
                    $privilege_label = $this->_i18n->get_string("privilege {$privilege_components[1]}", $privilege_components[0]);
                }

                if (!isset($header_items[$privilege_label])) {
                    $header_items[$privilege_label] = "        <th scope=\"col\" class=\"{$privilege_components[1]}\"><span>" . $this->_l10n->get($privilege_label) . "</span></th>\n";
                }

                $fields[str_replace([':', '.'], '_', $assignee . '_' . $privilege)] = [
                    'title' => $privilege_label,
                    'storage' => null,
                    'type' => 'privilege',
                    'type_config' => [
                        'privilege_name' => $privilege,
                        'assignee'       => $assignee,
                    ],
                    'widget' => 'privilegeselection'
                ];
            }
        }
        $schemadb->get('privileges')->set('fields', $fields);

        $header .= "        <th scope=\"col\" class=\"assignee_name\"><span>&nbsp;</span></th>\n";
        $header .= implode('', $header_items);
        $header .= "        <th scope=\"col\" class=\"row_actions\"><span>&nbsp;</span></th>\n";

        $this->_header = $header;
    }

    private function load_assignees()
    {
        $assignees = [];

        // Populate all resources having existing privileges
        $existing_privileges = $this->_object->get_privileges();
        if ($this->additional_assignee) {
            $existing_privileges[] = new midcom_core_privilege(['assignee' => $this->additional_assignee]);
        }
        foreach ($existing_privileges as $privilege) {
            if ($privilege->is_magic_assignee()) {
                // This is a magic assignee
                $label = $this->_l10n->get($privilege->assignee);
            } else {
                // Inconsistent privilege base will mess here. Let's give a chance to remove ghosts
                $assignee = midcom::get()->auth->get_assignee($privilege->assignee);

                if (is_object($assignee)) {
                    $label = $assignee->name;
                } else {
                    $label = $this->_l10n->get('ghost assignee for '. $privilege->assignee);
                }
            }

            $assignees[$privilege->assignee] = $label;

            $key = str_replace(':', '_', $privilege->assignee);
            if (!isset($this->_row_labels[$key])) {
                $this->_row_labels[$key] = $label;
            }
        }
        return $assignees;
    }

    public function get_schema_name()
    {
        return 'privileges';
    }

    /**
     * Object editing view
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_edit(Request $request, $handler_id, $guid, array &$data)
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $this->_object->require_do('midgard:privileges');
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        // Load possible additional component privileges
        $this->_load_component_privileges();

        if ($request->request->count() > 0) {
            $formdata = $request->request->all();
            $formdata = reset($formdata);
            $this->additional_assignee = $formdata['add_assignee'];
        }

        // Load the datamanager controller
        $this->_controller = $this->load_controller();

        switch ($this->_controller->handle($request)) {
            case 'save':
                //Fall-through
            case 'cancel':
                return new midcom_response_relocate($this->router->generate('object_view', ['guid' => $this->_object->guid]));
        }

        $this->_prepare_request_data();

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * Shows the loaded object in editor.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        $data['editor_header_titles'] = $this->_header;
        $data['row_labels'] = $this->_row_labels;

        $data['renderer'] = $this->_controller->get_datamanager()->get_renderer('form');
        $data['form'] = $data['renderer']->get_view();

        midcom_show_style('midgard_admin_asgard_object_permissions');
    }
}
