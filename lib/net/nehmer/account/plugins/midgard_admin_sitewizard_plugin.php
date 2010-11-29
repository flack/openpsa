<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a plugin for creating user home topic
 *
 * @package net.nehmer.account
 */
class midgard_admin_sitewizard_plugin extends midcom_baseclasses_components_handler
{
    var $_host_guid = '';
    var $_structure_config_path = '';
    var $_verbose = false;
    var $_home_name = '';
    var $_home_title = '';
    var $_creation_root_topic_guid = '';
    var $_creation_root_topic_parent_guid = '';
    var $_creation_root_topic_component = '';
    var $_creation_root_topic_parameters = array();
    var $_creation_root_group_guid = '';
    var $_creation_root_group_parent_guid ='';
    var $_creation_root_group_name = '';

    public function _on_initialize()
    {
        require_once($this->_request_data['plugin_config']['sitewizard_path']);

        $this->_host_guid = $this->_request_data['plugin_config']['host_guid'];
        $this->_creation_root_topic_style = $this->_request_data['plugin_config']['creation_root_topic_style'];
        $this->_creation_root_topic_component = $this->_request_data['plugin_config']['creation_root_topic_component'];
        $this->_creation_root_topic_parameters = $this->_request_data['plugin_config']['creation_root_topic_parameters'];
        $this->_creation_root_group_name = $this->_request_data['plugin_config']['creation_root_group_name'];

        $this->_redirect_after_done = $this->_request_data['plugin_config']['redirect_after_done'];

        $this->_structure_config_path = $this->_request_data['plugin_config']['structure_config_path'];

        if (isset($this->_request_data['plugin_config']['verbose']) && !empty($this->_request_data['plugin_config']['verbose']))
        {
            $this->_verbose = $this->_request_data['plugin_config']['verbose'];
        }

        if (isset($this->_request_data['plugin_config']['creation_root_topic_parent_guid'])
            && !empty($this->_request_data['plugin_config']['creation_root_topic_parent_guid']))
        {
            $this->_creation_root_topic_parent_guid = $this->_request_data['plugin_config']['creation_root_topic_parent_guid'];
        }
        else if (isset($this->_request_data['plugin_config']['creation_root_topic_guid'])
            && !empty($this->_request_data['plugin_config']['creation_root_topic_guid']))
        {
            $this->_creation_root_topic_guid = $this->_request_data['plugin_config']['creation_root_topic_guid'];
        }

    if (isset($this->_request_data['plugin_config']['creation_root_group_parent_guid'])
        && !empty($this->_request_data['plugin_config']['creation_root_group_parent_guid']))
    {
            $this->_creation_root_group_parent_guid = $this->_request_data['plugin_config']['creation_root_group_parent_guid'];
    }
    else if (isset($this->_request_data['plugin_config']['creation_root_group_guid'])
        && !empty($this->_request_data['plugin_config']['creation_root_group_guid']))
    {
            $this->_creation_root_group_guid = $this->_request_data['plugin_config']['creation_root_group_guid'];
    }
    }

    function get_plugin_handlers()
    {
        return array
        (
        'sitewizard' => array
        (
            'handler' => array('midgard_admin_sitewizard_plugin', 'create_user_home'),
        ),
    );
    }

    /**
     * @return boolean Indicating success.
     */
    public function _handler_create_user_home()
    {
        $user = $_MIDCOM->auth->user;

        $this->_home_name = $user->username;
        $this->_home_title = $user->name;

        return true;
    }

    public function _show_create_user_home()
    {
        echo "<pre>";
        try
        {
            $sitewizard = new midgard_admin_sitewizard();
            $sitewizard->set_verbose($this->_verbose);

            $structure_creator = $sitewizard->initialize_structure_creation($this->_host_guid);
            $structure_creator->read_config($this->_structure_config_path);

            if ($this->_creation_root_topic_guid != '')
            {
                $structure_creator->set_creation_root_topic($this->_creation_root_topic_guid);
            }
            elseif ($this->_creation_root_topic_parent_guid != '')
            {
                $structure_creator->create_creation_root_topic($this->_creation_root_topic_parent_guid,
                $this->_home_name, $this->_home_title, $this->_creation_root_topic_component,
                $this->_creation_root_topic_parameters, $this->_creation_root_topic_style, true);
            }

            if ($this->_creation_root_group_guid != '')
            {
                $structure_creator->set_creation_root_group($this->_creation_root_group_guid);
            }
            elseif ($this->_cretion_root_group_parent_guid != '')
            {
                $structure_creator->create_creation_root_group($this->_creation_root_group_guid,
                $this->_creation_root_group_name);
            }

            $structure_creator->execute();

            if (! empty($this->_redirect_after_done))
            {
                $_MIDCOM->relocate($this->_redirect_after_done);
            }
        }
        catch (midgard_admin_sitewizard_exception $e)
        {
            echo "<h2>Failed to create user home</h2>";
            echo "<p>";
            $e->error();
            echo "</p>";
        }
       echo "</pre>";
    }
}
?>