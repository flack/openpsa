<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is a host toolbar class.
 *
 * @package midcom.helper
 */
class midcom_helper_toolbar_host extends midcom_helper_toolbar
{
    public function __construct()
    {
        $config = midcom::get()->config;
        parent::__construct($config->get('toolbars_host_style_class'), $config->get('toolbars_host_style_id'));
        $this->label = midcom::get()->i18n->get_string('host', 'midcom');
        $this->add_commands();
    }

    private function add_commands()
    {
        $buttons = [];
        if (midcom::get()->auth->user) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "midcom-logout-",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('logout', 'midcom'),
                MIDCOM_TOOLBAR_GLYPHICON => 'sign-out',
                MIDCOM_TOOLBAR_ACCESSKEY => 'l',
            ];
        }

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/",
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
            MIDCOM_TOOLBAR_GLYPHICON => 'server',
            MIDCOM_TOOLBAR_ACCESSKEY => 'a',
            MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin'),
        ];

        if (midcom_connection::is_admin()) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "midcom-cache-invalidate",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('invalidate cache', 'midcom'),
                MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
            ];
            $workflow = new midcom\workflow\viewer;
            $buttons[] = $workflow->get_button(midcom_connection::get_url('self') . "midcom-config-test", [
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('test settings', 'midcom'),
                MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
            ]);
        }
        $this->add_items($buttons);
    }
}
