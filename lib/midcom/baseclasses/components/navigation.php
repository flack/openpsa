<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: navigation.php 26464 2010-06-28 13:15:28Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Base class to encapsulate a NAP interface. Does all the necessary work for
 * setting the object to the right topic. you just have to fill the gaps for
 * getting the leaves and node data.
 *
 * Normally, it is enough if you override the members list_leaves() and get_node().
 * You usually don't even need to write a constructor, as the default one should
 * be enough for your purposes. If you need extra initialization work done when
 * "entering" a topic, use the event handler _on_set_object().
 *
 * @package midcom.baseclasses
 */

class midcom_baseclasses_components_navigation
{
    /**#@+
     * Component state variable, set during startup. There should be no need to change it
     * in most cases.
     *
     * @access protected
     */

    /**
     * Internal helper, holds the name of the component. Should be used whenever the
     * components' name is required instead of hardcoding it.
     *
     * @var string
     */
    var $_component = '';

    /**
     * Component data storage area.
     *
     * @var Array
     */
    var $_component_data = null;

    /**
     * The topic for which we are handling a request.
     *
     * @var midcom_db_topic
     */
    var $_topic = null;

    /**
     * The current configuration.
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

    /**#@-*/

    /**
     * Create the navigation instance, the constructor doesn't do anything
     * yet, startup is handled by initialize().
     */
    public function __construct()
    {
        // Nothing to do
    }

    /**
     * Initialize the NAP class, sets all state variables.
     *
     * @param string $component The name of the component.
     */
    public function initialize($component)
    {
        $this->_component = $component;
        $this->_component_data =& $GLOBALS['midcom_component_data'][$this->_component];

        $this->_i18n = $_MIDCOM->get_service('i18n');
        $this->_l10n = $this->_i18n->get_l10n($this->_component);
        $this->_l10n_midcom = $this->_i18n->get_l10n('midcom');

        $this->_config = $this->_component_data['config'];
    }

    /**
     * Leaf listing function, the default implementation returns an empty array indicating
     * no leaves. Note, that the active leaf index set by the other parts of the component
     * must match one leaf out of this list.
     *
     * Here are some code fragments, that you usually connect through some kind of QB array:
     *
     * <code>
     * <?php
     *
     *  foreach ($articles as $article)
     *  {
     *      $leaves[$article->id] = array
     *      (
     *          MIDCOM_NAV_URL => $article->name . "/",
     *          MIDCOM_NAV_NAME => $article->title,
     *          MIDCOM_NAV_OBJECT => $article,
     *          MIDCOM_NAV_GUID => $article->guid,
     *      )
     *  }
     *
     *  return $leaves;
     *
     * ?>
     * </code>
     *
     * @return Array NAP compliant list of leaves.
     */
    public function get_leaves()
    {
        return Array();
    }

    /**
     * Return the node configuration. This defaults to use the topic the
     * NAP instance has been set to directly. You can usually fall back to this
     * behavior safely.
     *
     * The default uses the extra field of the topic as NAV_NAME
     *
     * @return Array NAP compliant node declaration
     */
    public function get_node()
    {
        if (!is_object($this->_topic->metadata))
        {
            return null;
        }

        return array (
            MIDCOM_NAV_URL => '',
            MIDCOM_NAV_NAME => $this->_topic->extra,
            MIDCOM_NAV_CONFIGURATION => $this->_config,
        );
    }


    /**
     * Set a new content object. This updates the local configuration copy with the
     * topic in question. It calls the event handler _on_set_object after initializing
     * everything in case you need to do some custom initializations as well.
     *
     * @param midcom_db_topic $topic The topic to process.
     * @return boolean Indicating success.
     */
    public function set_object($topic)
    {
        $this->_topic = $topic;
        $this->_config->store_from_object($topic, $this->_component);

        return $this->_on_set_object();
    }

    /**
     * Event handler called after a new topic has been set. The configuration is
     * already loaded at this point.
     *
     * @access protected
     * @return boolean Set this to false to indicate that you could not set this instance
     *   to the topic. NAP will abort loading this node and log the error accordingly.
     *   Return true if everything is fine.
     */
    function _on_set_object()
    {
        return true;
    }
}
?>