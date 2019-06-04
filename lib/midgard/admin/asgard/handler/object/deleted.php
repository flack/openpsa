<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\storage\connection;

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
     *
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_deleted($guid, array &$data)
    {
        $data['view_title'] = $this->_l10n->get('object deleted');

        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));

        if (midcom::get()->auth->admin) {
            $this->prepare_admin_view($guid);
        }

        $this->add_breadcrumb("", $data['view_title']);
        return $this->get_response();
    }

    private function prepare_admin_view($guid)
    {
        $type = connection::get_em()
            ->createQuery('SELECT r.typename from midgard:midgard_repligard r WHERE r.guid = ?1')
            ->setParameter(1, $guid)
            ->getSingleScalarResult();

        $dba_type = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($type);

        $qb = midcom::get()->dbfactory->new_query_builder($dba_type);
        $qb->include_deleted();
        $qb->add_constraint('guid', '=', $guid);

        $this->_request_data['object'] = $qb->get_result(0);

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
        $label = midcom_helper_reflector::get($this->_request_data['object'])->get_object_label($this->_request_data['object']);
        $this->add_breadcrumb('', $label);
    }

    /**
     * Output the style element for deleted objects
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_deleted($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_deleted');
    }
}
