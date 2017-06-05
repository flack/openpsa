<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Base class for components. Provides some common functionality that should be available
 * in all parts of the component's environment.
 *
 * @property midcom_services_i18n $_i18n A handle to the i18n service.
 * @property midcom_services_i18n_l10n $_l10n The components' L10n string database.
 * @property midcom_services_i18n_l10n $_l10n_midcom The global MidCOM string database.
 * @property midcom_helper_configuration $_config The current configuration.
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
    private $_services = [];

    /**
     * Initialize all member variables, remember to set $_component before calling
     * this constructor from your derived classes.
     */
    public function __construct()
    {
        if ($this->_component == '') {
            $this->_component = preg_replace('/^(.+?)_(.+?)_([^_]+).*/', '$1.$2.$3', get_class($this));
        }
    }

    public function __get($field)
    {
        if (array_key_exists($field, $this->_services)) {
            return $this->_services[$field];
        }

        switch ($field) {
            case '_i18n':
                $this->_services[$field] = midcom::get()->i18n;
                break;
            case '_l10n':
                $this->_services[$field] = midcom::get()->i18n->get_l10n($this->_component);
                break;
            case '_l10n_midcom':
                $this->_services[$field] = midcom::get()->i18n->get_l10n('midcom');
                break;
            case '_config':
                $this->_services[$field] = midcom_baseclasses_components_configuration::get($this->_component, 'config');
                break;
            default:
                debug_add('Component ' . $this->_component . ' tried to access nonexistent service "' . $field . '"', MIDCOM_LOG_ERROR);
                debug_print_function_stack('Called from here:');
                return false;
        }
        return $this->_services[$field];
    }

    public function __isset($field)
    {
        return (in_array($field, ['_i18n', '_l10n', '_l10n_midcom', '_config']));
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
        midcom::get()->head->add_stylesheet($url, $media);
    }

    /**
     *
     * @param string $identifier
     * @param array $options
     * @return \midcom\workflow\dialog
     */
    public function get_workflow($identifier, array $options = [])
    {
        $classname = '\\midcom\\workflow\\' . $identifier;
        return new $classname($options);
    }
}
