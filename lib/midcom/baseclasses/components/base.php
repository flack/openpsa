<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\workflow\dialog;

/**
 * Base trait for components. Provides some common functionality that should be available
 * in all parts of the component's environment.
 *
 * @property midcom_services_i18n $_i18n A handle to the i18n service.
 * @property midcom_services_i18n_l10n $_l10n The components' L10n string database.
 * @property midcom_services_i18n_l10n $_l10n_midcom The global MidCOM string database.
 * @property midcom_helper_configuration $_config The current configuration.
 * @package midcom.baseclasses
 */
trait midcom_baseclasses_components_base
{
    /**
     * The name of the component, e.g. net.nehmer.static. Should be used whenever the
     * component's name is required instead of hardcoding it.
     */
    public string $_component = '';

    /**
     * Initialize $_component (unless already set)
     */
    public function __construct()
    {
        if ($this->_component == '') {
            $this->_component = preg_replace('/^(.+?)_(.+?)_([^_]+).*/', '$1.$2.$3', static::class);
        }
    }

    public function __get($field)
    {
        switch ($field) {
            case '_i18n':
                return midcom::get()->i18n;
            case '_l10n':
                return midcom::get()->i18n->get_l10n($this->_component);
            case '_l10n_midcom':
                return midcom::get()->i18n->get_l10n('midcom');
            case '_config':
                return midcom_baseclasses_components_configuration::get($this->_component, 'config');
            default:
                debug_add('Component ' . $this->_component . ' tried to access nonexistent service "' . $field . '"', MIDCOM_LOG_ERROR);
                debug_print_function_stack('Called from here:');
                return false;
        }
    }

    public function __isset($field)
    {
        return in_array($field, ['_i18n', '_l10n', '_l10n_midcom', '_config']);
    }

    public function set_active_leaf($leaf_id)
    {
        midcom_baseclasses_components_configuration::set($this->_component, 'active_leaf', $leaf_id);
    }

    /**
     * Convenience shortcut for adding CSS files
     */
    public function add_stylesheet(string $url, string $media = null)
    {
        midcom::get()->head->add_stylesheet($url, $media);
    }

    public function get_workflow(string $identifier, array $options = []) : dialog
    {
        $classname = '\\midcom\\workflow\\' . $identifier;
        return new $classname($options);
    }
}
