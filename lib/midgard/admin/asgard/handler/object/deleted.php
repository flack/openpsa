<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\storage\connection;
use midcom\datamanager\datamanager;

/**
 * Simple object deleted page
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_deleted extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    /**
     * Handler for deleted objects
     */
    public function _handler_deleted(string $handler_id, string $guid, array &$data)
    {
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));

        if (midcom::get()->auth->admin) {
            $data['object'] = $this->prepare_admin_view($guid);
            if ($data['object']->metadata->deleted == false) {
                return new midcom_response_relocate($this->router->generate('object_open', ['guid' => $data['object']->guid]));
            }
            midgard_admin_asgard_plugin::bind_to_object($data['object'], $handler_id, $data);
        }
        $data['view_title'] = $this->_l10n->get('object deleted');

        $this->add_breadcrumb("", $data['view_title']);

        return $this->get_response('midgard_admin_asgard_object_deleted');
    }

    private function prepare_admin_view(string $guid) : midcom_core_dbaobject
    {
        $type = connection::get_em()
            ->createQuery('SELECT r.typename from midgard:midgard_repligard r WHERE r.guid = ?1')
            ->setParameter(1, $guid)
            ->getSingleScalarResult();

        $dba_type = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($type);

        $qb = midcom::get()->dbfactory->new_query_builder($dba_type);
        $qb->include_deleted();
        $qb->add_constraint('guid', '=', $guid);

        $object = $qb->get_result(0);
        $this->prepare_dm($type, $object);

        $this->_request_data['asgard_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('trash_type', ['type' => $type]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('undelete'),
            MIDCOM_TOOLBAR_GLYPHICON => 'recycle',
            MIDCOM_TOOLBAR_POST => true,
            MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                'undelete[]' => $guid
            ]
        ]);
        $this->_request_data['asgard_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('trash_type', ['type' => $type]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('purge'),
            MIDCOM_TOOLBAR_GLYPHICON => 'trash',
            MIDCOM_TOOLBAR_POST => true,
            MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                'undelete[]' => $guid,
                'purge' => true
            ]
        ]);
        $this->add_breadcrumb($this->router->generate('trash'), $this->_l10n->get('trash'));
        $this->add_breadcrumb($this->router->generate('trash_type', ['type' => $type]), midgard_admin_asgard_plugin::get_type_label($dba_type));
        $label = midcom_helper_reflector::get($object)->get_object_label($object);
        $this->add_breadcrumb('', $label);
        return $object;
    }

    /**
     * Loads the schemadb from the helper class
     */
    private function prepare_dm(string $type, midcom_core_dbaobject $object)
    {
        $schema_helper = new midgard_admin_asgard_schemadb($object, $this->_config, $type);
        $schemadb = $schema_helper->create([]);
        $datamanager = new datamanager($schemadb);
        $datamanager
            ->set_storage($object)
            ->get_form(); // currently needed to add head elements
        $this->_request_data['datamanager'] = $datamanager;
    }
}
