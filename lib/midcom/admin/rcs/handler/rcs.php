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

    protected function get_breadcrumbs()
    {
        $items = [];
        if (!is_a($this->object, 'midcom_db_topic')) {
            $items[] = [
                MIDCOM_NAV_URL => $this->get_object_url(),
                MIDCOM_NAV_NAME => $this->resolve_object_title()
            ];
        }
        return $items;
    }

    protected function handler_callback($handler_id)
    {
        $this->_view_toolbar->add_item(
            [
                MIDCOM_TOOLBAR_URL => $this->get_object_url(),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('back to %s'), $this->resolve_object_title()),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_up.png',
            ]
        );
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
    }
}
