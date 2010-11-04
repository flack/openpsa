<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: purecode.php 25325 2010-03-18 16:52:27Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Base class used for writing pure code components, retrieves a few common variables
 * from the components current environment.
 *
 * Note, that the request data, for ease of use, already contains references to the L10n
 * Databases of the Component and MidCOM itself located in this class. They are stored
 * as 'l10n' and 'l10n_midcom'. Also available as 'config' is the current component
 * configuration.
 *
 * @package midcom.baseclasses
 */
class midcom_baseclasses_components_purecode
{
    /**#@+
     * Request state variable.
     *
     * To initialize this variable you must call _bind_to_request_data(), otherwise
     * it will be null.
     *
     * @access protected
     */

    /**
     * The current configuration, possibly modified by the _load_topic_configuration
     * helper.
     *
     * @var midcom_helper_configuration
     */
    var $_config = null;

    /**
     * A handle to the i18n service.
     *
     * @var midcom_services_i18n
     */
    var $_i18n = null;

    /**
     * The components' L10n string database
     *
     * @var midcom_services__i18n_l10n
     */
    var $_l10n = null;

    /**
     * The global MidCOM string database
     *
     * @var midcom_services__i18n_l10n
     */
    var $_l10n_midcom = null;

    /**
     * Component data storage area.
     *
     * @var Array
     */
    var $_component_data = null;

    /**
     * Internal helper, holds the name of the component. Should be used whenever the
     * components' name is required instead of hardcoding it.
     *
     * This variable must be set before the baseclasses' constructor is called.
     *
     * @var string
     */
    var $_component = '';

    /**#@-*/

    /**
     * Initialize all member variables, remember to set $_component before calling
     * this constructor from your derived classes.
     */
    public function __construct()
    {
        $this->_component_data =& $GLOBALS['midcom_component_data'][$this->_component];
        $this->_i18n = $_MIDCOM->get_service('i18n');
        $this->_l10n = $this->_i18n->get_l10n($this->_component);
        $this->_l10n_midcom = $this->_i18n->get_l10n('midcom');
        $this->_load_topic_configuration(null);
    }

    /**
     * This is an internal helper which you can use to merge a topic configuration set
     * for your component into your local configuration store. Call this without an
     * argument to reset to the global defaults.
     *
     * @param midcom_db_topic $topic The topic from which to load the configuration. Omit this
     *     (or call it with null) to load the global default configuration.
     * @access protected
     */
    private function _load_topic_configuration($topic = null)
    {
        $this->_config = $GLOBALS['midcom_component_data'][$this->_component]['config'];
        if ($topic !== null)
        {
            $this->_config->store_from_object($topic, $this->_component);
        }
    }

    /**
     * Binds the object to the current request data. This populates the members
     * _request_data, _config, _topic, _l10n and _l10n_midcom accordingly.
     *
     * @access protected
     */
    private function _bind_to_request_data()
    {
        $this->_request_data =& $_MIDCOM->get_custom_context_data('request_data');
        $this->_config =& $this->_request_data['config'];
        $this->_topic =& $this->_request_data['topic'];
        $this->_l10n =& $this->_request_data['l10n'];
        $this->_l10n_midcom =& $this->_request_data['l10n_midcom'];
    }


}

?>