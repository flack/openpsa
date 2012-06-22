<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Base class for components. Provides some common functionality that should be available
 * in all parts of the component's environment. Available services are
 *
 * <b>midcom_services_i18n $_i18n</b> A handle to the i18n service.
 * <b>midcom_services_i18n_l10n $_l10n</b> The components' L10n string database.
 * <b>midcom_services_i18n_l10n $_l10n_midcom</b> The global MidCOM string database.
 * <b>midcom_helper_configuration $_config</b> The current configuration.
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_base
{
    /**
     * The name of the component, e.g. net.nehmer.static. Should be used whenever the
     * component's name is required instead of hardcoding it.
     *
     * @var string
     */
    public $_component = '';

    /**
     * Array that holds the already instantiated services
     *
     * @var array
     */
    private $_services = array();

    public function __construct(){}

    public function __get($field)
    {
        if (array_key_exists($field, $this->_services))
        {
            return $this->_services[$field];
        }

        $instance = null;
        switch ($field)
        {
            case '_i18n':
                $instance = midcom::get('i18n');
                break;
            case '_l10n':
                $instance = midcom::get('i18n')->get_l10n($this->_component);
                break;
            case '_l10n_midcom':
                $instance = midcom::get('i18n')->get_l10n('midcom');
                break;
            case '_config':
                $instance = midcom_baseclasses_components_configuration::get($this->_component, 'config');
                break;
            default:
                debug_add('Component ' . $this->_component . ' tried to access nonexistant service "' . $field . '"', MIDCOM_LOG_ERROR);
                debug_print_function_stack('Called from here:');
                return false;
        }
        $this->_services[$field] = $instance;
        return $this->_services[$field];
    }

    public function __isset($field)
    {
        switch ($field)
        {
            case '_i18n':
            case '_l10n':
            case '_l10n_midcom':
            case '_config':
                return true;
            default:
                return false;
        }
    }

    public function set_active_leaf($leaf_id)
    {
        midcom_baseclasses_components_configuration::set($this->_component, 'active_leaf', $leaf_id);
    }

    /**
     * Convenience shortcut for adding CSS files
     *
     * @param string $url The stylesheet URL
     * @param string $media The media type(s) for the stylesheet, if any
     */
    public function add_stylesheet($url, $media = false)
    {
        midcom::get('head')->add_stylesheet($url, $media);
    }
}
?>
