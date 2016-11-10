<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Simple object deleted page
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_deleted extends midcom_baseclasses_components_handler
{
    /**
     * Handler for deleted objects
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_deleted($handler_id, array $args, array &$data)
    {
        $data['guid'] = $args[0];
        /*
         * TODO: It would be nice to be able to load the object to show undelete/purge links, but for
         * this we'd have to loop through all schema types and qb until we find something ...
         */

        if (midcom::get()->auth->admin) {
            $data['asgard_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => '__mfa/asgard/trash/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('trash'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash-full.png',
                )
            );
        }

        $data['view_title'] = $this->_l10n->get('object deleted');

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get($this->_component));
        $this->add_breadcrumb("", $data['view_title']);
        return new midgard_admin_asgard_response($this, '_show_deleted');
    }

    /**
     * Output the style element for deleted objects
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_deleted($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_object_deleted');
    }
}
