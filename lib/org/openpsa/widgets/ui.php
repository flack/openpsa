<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class to load parts of the ui
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_ui extends midcom_baseclasses_components_purecode
{
    public static function get_config_value($value)
    {
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.widgets', 'config');
        return $config->get($value);
    }

    public static function get_search_providers() : array
    {
        $defaults = ['autocomplete' => false];
        $providers = [];
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $configured_providers = self::get_config_value('search_providers');
        $user_id = false;

        if (!midcom::get()->auth->admin) {
            $user_id = midcom::get()->auth->acl->get_user_id();
        }
        foreach ($configured_providers as $component => $config) {
            if (!is_array($config)) {
                $config = ['route' => $config];
            }
            $config = array_merge($defaults, $config);

            $node_url = $siteconfig->get_node_full_url($component);
            if (   $node_url
                && (   !$user_id
                    || midcom::get()->auth->acl->can_do_byguid('midgard:read', $siteconfig->get_node_guid($component), midcom_db_topic::class, $user_id))) {
                $providers[] = [
                    'placeholder' => midcom::get()->i18n->get_string('search title', $component),
                    'url' => $node_url . $config['route'],
                    'identifier' => $component,
                    'autocomplete' => $config['autocomplete'],
                ];
            }
        }
        return $providers;
    }

    public static function add_head_elements()
    {
        $head = midcom::get()->head;
        $head->enable_jquery();

        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.widgets/ui.js');
    }

    /**
     * Render jquery.ui tab controls. Relatedto tabs are automatically added if a GUID is found
     * Also adds the necessary javascript & css files for ui_tab
     *
     * @param string $guid The GUID, if any
     * @param array $tabdata Any custom tabs the handler wants to add
     */
    public static function render_tabs($guid, array $tabdata)
    {
        $head = midcom::get()->head;
        $head->enable_jquery_ui(['tabs']);
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.widgets/tab_functions.js');

        $uipage = self::get_config_value('ui_page');
        $prefix = midcom_connection::get_url('self') . $uipage . '/';

        if (!empty($guid)) {
            //pass the urls & titles for the tabs
            $tabdata[] = [
               'url' => '__mfa/org.openpsa.relatedto/journalentry/' . $guid . '/',
               'title' => midcom::get()->i18n->get_string('journal entries', 'org.openpsa.relatedto'),
            ];
            $tabdata[] = [
               'url' => '__mfa/org.openpsa.relatedto/render/' . $guid . '/both/',
               'title' => midcom::get()->i18n->get_string('related objects', 'org.openpsa.relatedto'),
            ];
        }

        echo '<div id="tabs">';
        echo "\n<ul>\n";
        foreach ($tabdata as $key => $tab) {
            echo "<li><a id='key_" . $key ."' class='tabs_link' href='" . $prefix . $tab['url'] . "' ><span> " . $tab['title'] . "</span></a></li>";
        }
        echo "\n</ul>\n";
        echo "</div>\n";

        echo <<<JSINIT
<script type="text/javascript">
    org_openpsa_widgets_tabs.initialize('{$uipage}');
</script>
JSINIT;
    }

    /**
     * @param array $items
     */
    public static function add_navigation_toolbar(array $items)
    {
        $toolbar = new midcom_helper_toolbar('midcom_toolbar navigation_toolbar');
        $toolbar->add_items($items);
        midcom::get()->toolbars->add_toolbar('navigation', $toolbar);
    }
}
