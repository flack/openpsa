<?php
/**
 * @package midcom
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class encapsulates the operations needed to configure a MidCom host as per mRFC 0025
 *
 * @link http://www.midgard-project.org/development/mrfc/0025/ mRFC 0025
 * @package midcom
 */
class midcom_helper_hostconfig
{
    /**
     * The array containing various configuration details
     * @access private
     */
    var $config = array();

    /**
     * the path to the midcom lib directory.
     * Defaults to a PEAR installation.
     * Appends midcom.php itself.
     * @access public
     */
    var $midcom_path = 'midcom';

    /**
     * Version of the hostsetup.
     * @access public
     */
    var $version = '1.0.0';

    /**
     * Domain for the cached setting parameters
     * @access public
     */
    var $setting_parameter_domain = 'midgard';

    /**
     * The page to save the configuration on.
     */
    var $page = null;

    var $values_to_skip = array
    (
        'midcom_path'           => true,
        'configuration_version' => true,
        'midcom_helper_datamanager2_save' => true,
    );

    function __construct(&$page)
    {
        $this->page = $page;
        $this->get_configuration();
    }

    /**
     * Read current configuration from root page parameters
     *
     */
    function get_configuration()
    {
        $params = $this->page->list_parameters($this->setting_parameter_domain);

        if (!empty($params))
        {
            $configuration_keys_handled = array();
            foreach ($params as $name => $value)
            {
                $this->config[$name] = $value;
                $configuration_keys_handled[$name] = true;
            }
        }

        if ($this->page->id != $_MIDGARD['page'])
        {
            // We're editing config of another MidCOM page
            return;
        }

        // Read run-time configuration
        foreach ($GLOBALS['midcom_config_local'] as $name => $value)
        {
            if ($value === false)
            {
                // HTML_QuickForm doesn't like booleans
                $value = 0;
            }

            $this->config[$name] = $value;
            $configuration_keys_handled[$name] = true;
        }
    }

    /**
     * Cache the configuration to page parameters.
     * @return boolean Whether all parameters saved successfully
     */
    function update_configuration()
    {
        if ($this->page === null)
        {
            return false; // I WANT EXCEPTIONS!!!
        }

        $status = true;
        foreach ($this->config as $key => $value)
        {
            // FIXME: For some reason there can be duplication here
            if ($value == '')
            {
                if (method_exists($this->page, 'delete_parameter'))
                {
                    // Empty config value, try deleting param just in case
                    debug_add("Empty config value, try deleting param just in case", MIDCOM_LOG_ERROR);
                    $this->page->delete_parameter($this->setting_parameter_domain, $key);
                }
                // And then skip
                continue;
            }

            if (!$this->page->parameter($this->setting_parameter_domain, $key, $value))
            {
                if (function_exists('debug_add'))
                {
                    debug_add("Failed to store configuration key {$key} as parameter: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
                $status = false;
            }
        }

        // Store the version too
        if (!$this->page->parameter($this->setting_parameter_domain, 'configuration_version', $this->version))
        {
            if (function_exists('debug_add'))
            {
                debug_add("Failed to store configuration version as parameter: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
            $status = false;
        }

        return $status;
    }

    /**
     * Generate the content of code init.
     */
    function get_code_init($generator = 'midcom_helper_hostconfig')
    {
        // Try to cache the configuration to parameters
        if (!$this->update_configuration())
        {
            return '';
        }
        $template = '$GLOBALS["midcom_config_local"]["{__NAME__}"] = {__VALUE__};';
        $configuration = array();

        $params = $this->page->list_parameters($this->setting_parameter_domain);

        if (!empty($params))
        {
            $configuration_keys_handled = array();
            foreach ($params as $name=>$value)
            {
                if (   !array_key_exists($name, $this->values_to_skip)
                    && !array_key_exists($name, $configuration_keys_handled)
                    && $value != '')
                {
                    $txt = $template;
                    $txt = str_replace('{__NAME__}', $name, $txt);

                    if (is_numeric($value))
                    {
                        $txt = str_replace('{__VALUE__}', $value, $txt);
                    }
                    else
                    {
                        $txt = str_replace('{__VALUE__}', "\"{$value}\"", $txt);
                    }

                    $configuration[] = $txt;
                }
                $this->config[$name] = $value;

                // Safeguard against duplicate values
                $configuration_keys_handled[$name] = true;
            }
        }

        // Start the code-init element with a warning
        $codeinit  = "<?php \n";
        $codeinit .= "/**\n";
        $codeinit .= " * MidCOM website configuration format version {$this->version} (mRFC 0025 specification)\n";
        $codeinit .= " * This element was generated using midcom_helper_hostconfig. Do not edit manually!\n";
        $codeinit .= " * Settings can be overridden and PHP executed in the code-init-before-midcom and code-init-after-midcom elements.\n";
        $codeinit .= " *\n";
        $codeinit .= " * Generated on " . gmdate('Y-m-d H:i:s \Z', time()) . " using {$generator}\n";

        if (   isset($_MIDCOM)
            && $_MIDCOM->auth->user)
        {
            $user = $_MIDCOM->auth->user->get_storage();
            $codeinit .= " * Settings selected by {$user->name} <{$user->email}>\n";
        }

        $codeinit .= " */\n";

        // Add configuration settings to the element
        $codeinit .= implode ("\n", $configuration) . "\n";

        // Hook that may be used for local settings like MIDCOM_ROOT on SVN-powered installations
        $codeinit .= "\n?><(code-init-before-midcom)><?php\n";

        if (!defined('MIDCOM_ROOT'))
        {
            $root = dirname(dirname(dirname(__FILE__)));
        }
        else
        {
            $root = MIDCOM_ROOT;
        }
        $codeinit .= "\nif (!defined(\"MIDCOM_ROOT\"))\n{";

        $codeinit .= "\n    define(\"MIDCOM_ROOT\", \"{$root}\");";
        $codeinit .= "\n}\n";

        // Include MidCOM
        $codeinit .= "\nrequire MIDCOM_ROOT . \"/midcom.php\";\n";

        // Hook that may be used for local settings like language handling
        $codeinit .= "\n?><(code-init-after-midcom)><?php\n\n";

        // Run MidCOM codeinit() phase
        $codeinit .= '$_MIDCOM->codeinit();' . "\n";
        $codeinit .= "?>";

        return $codeinit;
    }

    /**
     * Set a configuration value
     * @param string name name of the configuration value
     * @param string value the value
     * @access public
     */
    function set($name, $value)
    {
        if ($name == 'midcom_path')
        {
            $this->midcom_path = $value;
            return;
        }
        $this->config[$name] = $value;
        return;
    }

    /**
     * Get a config value
     * @return string the value
     * @param string name of the config value.
     * @access public
     */
    function get($name)
    {
        return $this->config[$name];
    }
}
?>