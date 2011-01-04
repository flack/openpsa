<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class to load parts of the ui
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_ui extends midcom_baseclasses_components_purecode
{
    /**
     * function that loads the necessary javascript & css files for jqgrid
     */
    public static function enable_jqgrid()
    {
        $jqgrid_path = '/org.openpsa.core/jquery.jqGrid-' . self::get_config_value('jqgrid_version') . '/';

        //first enable jquery - just in case it isn't loaded
        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');

        //needed js/css-files for jqgrid
        $lang = "en";
        $language = $_MIDCOM->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . $jqgrid_path . 'js/i18n/grid.locale-' . $language . '.js'))
        {
            $lang = $language;
        }
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'js/i18n/grid.locale-'. $lang . '.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . $jqgrid_path . 'js/jquery.jqGrid.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/jqGrid.custom.js');

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.resizable.min.js');

        $_MIDCOM->add_stylesheet(MIDCOM_STATIC_URL . $jqgrid_path . 'css/ui.jqgrid.css');
        $_MIDCOM->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/ui.custom.css');
    }

    public static function get_config_value($value)
    {
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.core', 'config');
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

        if ($search_url = $siteconfig->get_node_full_url('midcom.helper.search'))
        {
            $providers[] = array
            (
                'helptext' => $_MIDCOM->i18n->get_string('search', 'midcom.helper.search'),
                'url' => $search_url . 'result/',
                'identifier' => 'midcom.helper.search'
            );
        }
        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts'))
        {
            $providers[] = array
            (
                'helptext' => $_MIDCOM->i18n->get_string('contact search', 'org.openpsa.contacts'),
                'url' => $contacts_url . 'search/',
                'identifier' => 'org.openpsa.contacts'
            );
        }
        if ($documents_url = $siteconfig->get_node_full_url('org.openpsa.documents'))
        {
            $providers[] = array
            (
                'helptext' => $_MIDCOM->i18n->get_string('document search', 'org.openpsa.documents'),
                'url' => $documents_url . 'search/',
                'identifier' => 'org.openpsa.documents'
            );
        }
        if ($invoices_url = $siteconfig->get_node_full_url('org.openpsa.invoices'))
        {
            $providers[] = array
            (
                'helptext' => $_MIDCOM->i18n->get_string('go to invoice number', 'org.openpsa.invoices'),
                'url' => $invoices_url . 'goto/',
                'identifier' => 'org.openpsa.invoices'
            );
        }



        return $providers;
    }

    /**
     * Function to load the necessary javascript & css files for ui_tab
     */
    public static function enable_ui_tab()
    {
        //first enable jquery - just in case it isn't loaded
        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');

        //load ui-tab
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.tabs.min.js');

        //functions needed for ui-tab to work here
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.history.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/tab_functions.js');

        //add the needed css-files
        $_MIDCOM->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/ui.custom.css');
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

        $wait = $_MIDCOM->i18n->get_string('loading', 'org.openpsa.core');

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