<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Base class to encapsulate a NAP interface. Does all the necessary work for
 * setting the object to the right topic. You just have to fill the gaps for
 * getting the leaves and node data.
 *
 * Normally, it is enough if you override the members list_leaves() and get_node().
 * You usually don't even need to write a constructor, as the default one should
 * be enough for your purposes. If you need extra initialization work done when
 * "entering" a topic, use the event handler _on_set_object().
 *
 * @package midcom.baseclasses
 */
class midcom_baseclasses_components_navigation extends midcom_baseclasses_components_base
{
    /**
     * The topic for which we are handling a request.
     *
     * @var midcom_db_topic
     */
    protected $_topic;

    /**
     * Initialize the NAP class, sets all state variables.
     *
     * @param string $component The name of the component.
     */
    public function __construct($component)
    {
        $this->_component = $component;

        $this->_i18n = midcom::get()->i18n;
        $this->_l10n = $this->_i18n->get_l10n($this->_component);
        $this->_l10n_midcom = $this->_i18n->get_l10n('midcom');
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
     * </code>
     *
     * @return array NAP compliant list of leaves.
     */
    public function get_leaves()
    {
        return [];
    }

    /**
     * Return the node configuration. This defaults to use the topic the
     * NAP instance has been set to directly. You can usually fall back to this
     * behavior safely.
     *
     * The default uses the extra field of the topic as NAV_NAME
     *
     * @return array NAP compliant node declaration
     */
    public function get_node()
    {
        if (!is_object($this->_topic->metadata)) {
            return null;
        }

        return [
            MIDCOM_NAV_URL => '',
            MIDCOM_NAV_NAME => $this->_topic->extra,
            MIDCOM_NAV_CONFIGURATION => $this->_config,
        ];
    }

    /**
     * Set a new content object. This updates the local configuration copy with the
     * topic in question. It calls the event handler _on_set_object after initializing
     * everything in case you need to do some custom initializations as well.
     *
     * @param midcom_db_topic $topic The topic to process.
     * @return boolean Indicating success.
     */
    public function set_object($topic) : bool
    {
        $this->_topic = $topic;
        $this->_config->store_from_object($topic, $this->_component);

        return $this->_on_set_object();
    }

    /**
     * Event handler called after a new topic has been set. The configuration is
     * already loaded at this point.
     *
     * @return boolean Set this to false to indicate that you could not set this instance
     *   to the topic. NAP will abort loading this node and log the error accordingly.
     *   Return true if everything is fine.
     */
    public function _on_set_object()
    {
        return true;
    }
}
