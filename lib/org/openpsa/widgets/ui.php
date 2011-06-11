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

    /**
     * Helper function that returns information about available search providers
     *
     * @return array
     */
    public static function get_search_providers()
    {
        $providers = array();
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $configured_providers = self::get_config_value('search_providers');
        $user_id = false;

        if (!$_MIDCOM->auth->admin)
        {
            $user_id = $_MIDCOM->auth->acl->get_user_id();
        }

        foreach ($configured_providers as $component => $route)
        {
            $node_url = $siteconfig->get_node_full_url($component);
            if (   $node_url
                && (   !$user_id
                    || $_MIDCOM->auth->acl->can_do_byguid('midgard:read', $siteconfig->get_node_guid($component), 'midcom_db_topic', $user_id)))
            {
                $providers[] = array
                (
                    'helptext' => $_MIDCOM->i18n->get_string('search title', $component),
                    'url' => $node_url . $route,
                    'identifier' => $component
                );
            }
        }

        return $providers;
    }

    /**
     * Add necessary head elements for dynatree
     */
    public static function enable_dynatree()
    {
        $head = midcom::get('head');
        $head->enable_jquery();

        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');

        $head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.cookie.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.widgets/dynatree/jquery.dynatree.min.js');
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.widgets/dynatree/skin/ui.dynatree.css");
        $head->add_jquery_ui_theme();
    }

    /**
     * Function to load the necessary javascript & css files for ui_tab
     */
    public static function enable_ui_tab()
    {
        $head = midcom::get('head');
        //first enable jquery - just in case it isn't loaded
        $head->enable_jquery();

        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');

        //load ui-tab
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.tabs.min.js');

        //functions needed for ui-tab to work here
        $head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.history.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.widgets/tab_functions.js');

        //add the needed css-files
        $head->add_jquery_ui_theme(array('tabs'));
    }

    /**
     * Helper function to render jquery.ui tab controls. Relatedto tabs are automatically added
     * if a GUID is found
     *
     * @param string $guid The GUID, if any
     * @param array $tabdata Any custom tabs the handler wnats to add
     */
    public static function render_tabs($guid = null, $tabdata = array())
    {
        $uipage = self::get_config_value('ui_page');
        //set the url where the data for the tabs are loaded
        $data_url_prefix = $_MIDCOM->get_host_prefix() . $uipage;

        if (null !== $guid)
        {
            //pass the urls & titles for the tabs
            $tabdata[] = array
            (
               'url' => '/__mfa/org.openpsa.relatedto/journalentry/' . $guid . '/html/',
               'title' => $_MIDCOM->i18n->get_string('journal entries', 'org.openpsa.relatedto'),
            );
            $tabdata[] = array
            (
               'url' => '__mfa/org.openpsa.relatedto/render/' . $guid . '/both/',
               'title' => $_MIDCOM->i18n->get_string('related objects', 'org.openpsa.relatedto'),
            );
        }

        echo '<div id="tabs">';
        echo "\n<ul>\n";
        foreach ($tabdata as $key => $tab)
        {
            $url = $data_url_prefix . '/' . $tab['url'];
            echo "<li><a id='key_" . $key ."' class='tabs_link' href='" . $url . "' ><span> " . $tab['title'] . "</span></a></li>";
        }
        echo "\n</ul>\n";
        echo "</div>\n";

        $wait = $_MIDCOM->i18n->get_string('loading', 'org.openpsa.widgets');

        echo <<<JSINIT
<script type="text/javascript">
$(document).ready(
    function()
    {
        $('.ui-state-active a').live('mouseup', function(event)
        {
            if (event.which != 1)
            {
                return;
            }
            var url = $.data(event.currentTarget, 'href.tabs').replace(/\/{$uipage}\//, '/');
            location.href = url;
        });

        var tabs = $('#tabs').tabs({
              cache: true,
              spinner: '{$wait}...',
              load: function(){org_openpsa_jsqueue.execute();}
        });

        $.history.init(function(url)
        {
            var tab_id = 0;
            if (url != '')
            {
                tab_id = parseInt(url.replace(/ui-tabs-/, '')) - 1;
            }

            if ($('#tabs').tabs('option', 'selected') != tab_id)
            {
                $('#tabs').tabs('select', tab_id);
            }
        });

        $('#tabs a.tabs_link').bind('click', function(event)
        {
            var url = $(this).attr('href');
            url = url.replace(/^.*#/, '');
            $.history.load(url);
            return true;
        });

        $('#tabs a').live('click', function(event){intercept_clicks(event)});
    }
);
</script>
JSINIT;
    }
}
?>