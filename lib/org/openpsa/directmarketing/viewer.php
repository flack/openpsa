<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.directmarketing site interface class.
 *
 * Direct marketing and mass mailing lists
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_viewer extends midcom_baseclasses_components_request
{
    /**
     * Populates the node toolbar depending on the user's rights.
     */
    private function _populate_node_toolbar()
    {
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config'))
        {
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'config/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
                )
            );
        }
    }

    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, $args)
    {
        // Always run in uncached mode
        midcom::get('cache')->content->no_cache();

        // This component uses Ajax, include the handler javascripts
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/ajaxutils.js");
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/messages.js");

        $this->_populate_node_toolbar();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        if (midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_directmarketing_campaign_dba'))
        {
            $schemadb_campaign = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
            foreach (array_keys($schemadb_campaign) as $name)
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "campaign/create/{$name}/",
                        MIDCOM_TOOLBAR_LABEL => sprintf
                        (
                            $this->_l10n_midcom->get('create %s'),
                            $this->_l10n->get($schemadb_campaign[$name]->description)
                        ),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                    )
                );
            }
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_frontpage($handler_id, array &$data)
    {
        midcom_show_style("show-frontpage");
    }

    function get_messagetype_icon($type)
    {
        $icon = 'stock_mail.png';
        switch ($type)
        {
            case org_openpsa_directmarketing_campaign_message_dba::SMS:
            case org_openpsa_directmarketing_campaign_message_dba::MMS:
                $icon = 'stock_cell-phone.png';
                break;
            case org_openpsa_directmarketing_campaign_message_dba::CALL:
            case org_openpsa_directmarketing_campaign_message_dba::FAX:
                $icon = 'stock_landline-phone.png';
                break;
            case org_openpsa_directmarketing_campaign_message_dba::SNAILMAIL:
                $icon = 'stock_home.png';
                break;
        }
        return $icon;
    }

    function get_messagetype_css_class($type)
    {
        $class = 'email';
        switch ($type)
        {
            case org_openpsa_directmarketing_campaign_message_dba::SMS:
            case org_openpsa_directmarketing_campaign_message_dba::MMS:
                $class = 'mobile';
                break;
            case org_openpsa_directmarketing_campaign_message_dba::CALL:
            case org_openpsa_directmarketing_campaign_message_dba::FAX:
                $class = 'telephone';
                break;
            case org_openpsa_directmarketing_campaign_message_dba::SNAILMAIL:
                $class = 'postal';
                break;
        }
        return $class;
    }

    public function load_campaign($identifier)
    {
        $campaign = new org_openpsa_directmarketing_campaign_dba($identifier);
        if ($campaign->node != $this->_topic->id)
        {
            throw new midcom_error_notfound("The campaign {$identifier} was not found.");
        }
        return $campaign;
    }
}
?>