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
            && $this->_topic->can_do('midcom:component_config')) {
            $workflow = $this->get_workflow('datamanager2');
            $this->_node_toolbar->add_item($workflow->get_button('config/', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('component configuration'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            ]));
        }
    }

    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
        $this->_populate_node_toolbar();
    }

    /**
     * Prepare the schemadb
     */
    public function load_schemas()
    {
        $schemadbs = [
            'person' => midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person')),
            'campaign_member' => midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign_member')),
            'organization' => midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_organization')),
            'organization_member' => midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_organization_member')),
        ];

        foreach ($schemadbs as $name => $db) {
            if (!$db) {
                throw new midcom_error('Could not load ' . $name . ' schema database.');
            }
        }
        return $schemadbs;
    }

    public static function get_messagetype_icon($type)
    {
        $icon = 'stock_mail.png';
        switch ($type) {
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

    public function load_campaign($identifier)
    {
        $campaign = new org_openpsa_directmarketing_campaign_dba($identifier);
        if ($campaign->node != $this->_topic->id) {
            throw new midcom_error_notfound("The campaign {$identifier} was not found.");
        }
        $this->set_active_leaf('campaign_' . $campaign->id);
        return $campaign;
    }
}
