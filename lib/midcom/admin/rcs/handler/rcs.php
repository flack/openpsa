<?php
/**
 * @package midcom.admin.rcs
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 */

/**
 * @package midcom.admin.rcs
 */
class midcom_admin_rcs_handler_rcs extends midcom_services_rcs_handler
{
    protected $style_prefix = 'midcom-admin-rcs-';

    protected $url_prefix = '__ais/rcs/';

    protected function handler_callback($handler_id)
    {
        $parts = explode('_', $handler_id);
        $mode = end($parts);

        $this->prepare_request_data($mode);
    }

    protected function get_object_url()
    {
        return midcom::get()->permalinks->create_permalink($this->object->guid);
    }

    /**
     * Load the statics & prepend styledir
     */
    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('midcom.admin.rcs');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.admin.rcs/rcs.css");
    }

    private function prepare_request_data($mode)
    {
        $this->_view_toolbar->add_item(
            array(
                MIDCOM_TOOLBAR_URL => $this->get_object_url(),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('back to %s'), $this->resolve_object_title()),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_up.png',
            )
        );

        if (!is_a($this->object, 'midcom_db_topic')) {
            $this->add_breadcrumb($this->get_object_url(), $this->resolve_object_title());
        }
        $this->add_breadcrumb("__ais/rcs/{$this->object->guid}/", $this->_l10n->get('show history'));

        if ($mode == 'diff') {
            $this->add_breadcrumb(
                "__ais/rcs/preview/{$this->object->guid}/{$this->_request_data['latest_revision']}/",
                sprintf($this->_l10n->get('version %s'), $this->_request_data['latest_revision'])
            );
            $this->add_breadcrumb(
                "__ais/rcs/diff/{$this->object->guid}/{$this->_request_data['previous_revision']}/{$this->_request_data['latest_revision']}/",
                sprintf($this->_l10n->get('changes from version %s'), $this->_request_data['previous_revision'])
            );
        } elseif ($mode == 'preview') {
            $this->add_breadcrumb(
                    "__ais/rcs/preview/{$this->object->guid}/{$this->_request_data['revision']}/",
                sprintf($this->_l10n->get('version %s'), $this->_request_data['revision'])
            );
        }

        midcom::get()->head->set_pagetitle($this->_request_data['view_title']);
    }
}
