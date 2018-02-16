<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Baseclass to use for the component interface in MidCOM.
 *
 * The class uses an event based approach for subclasses to influence the default behavior.
 *
 * The actual implementation should be enough for most smaller components, as
 * the class's behavior is widely configurable. You should not override any of
 * the base class's interface methods if you can avoid it. If you find that an
 * event handler is missing, please contact the MidCOM development team for
 * some advice.
 *
 * <b>Quick start</b>
 *
 * This class does a lot of things automatically for you, described below. If you
 * are not interested in the gory details though, here is a quick list of what
 * you have to know (but don't complain if I have missed anything), and do these
 * things after calling the base class constructor:
 *
 * - Inherit the class as {$component}_interface (e.g. net_nehmer_static_interface).
 * - Prepare a component manifest for your component, see the class
 *   midcom_core_manifest for details.
 * - You can change the values of all public members, the defaults should be suitable for most cases.
 * - The component's data storage area will contain two keys when the initialized
 *   event handler is called: The NAP active id, defaulting to false and stored
 *   as <i>active_leaf</i> and the component's default configuration, stored as
 *   a midcom_helper_configuration object in the key <i>config</i>.
 * - Put your component-wide default configuration into $component_dir/config/config.inc.
 *
 * <b>Class parameters</b>
 *
 * The following options can be used to parametrize the component's startup and operation.
 * See the individual member documentation for now.
 *
 * - $_component
 * - $_nap_class_suffix
 * - $_site_class_suffix
 *
 * <b>Class operation</b>
 *
 * This class now does an awful lot of work for you, you just need to configure it
 * to have the right names and places to look for. It is designed to fit in the
 * current component wildlife with as little collateral damage as possible, but as
 * always, a 100% transparent implementation is neither wanted nor sensible.
 *
 * Actually, you should not need to overwrite any event handler for almost all components
 * I currently know of. Ultimately, this is a proxy class to the actual component code.
 * Its main goals are to automate these tasks:
 *
 * - Component startup (loading of the right script files and libraries)
 * - Default configuration (loading of the various configuration files, see the
 *   _load_configuration() method for details)
 * - Component data storage initialization
 * - Context separation during runtime
 *
 * <b>Example usage</b>
 *
 * The average component will require something like this, part one is the component
 * manifest:
 *
 * <code>
 * 'name' => 'net.nehmer.static',
 * 'purecode' => false,
 * 'version' => 1,
 * 'privileges' =>  Array
 * (
 *     'read' => MIDCOM_PRIVILEGE_ALLOW,
 *     'write' => Array (MIDCOM_PRIVILEGE_DENY, MIDCOM_PRIVILEGE_ALLOW)
 * ),
 * 'class_mapping' => array('mgdschema_classname' => 'midcom_classname'),
 * </code>
 *
 * See the class midcom_core_manifest for further details.
 *
 * Built on this, we add the following interface class:
 *
 * <code>
 * class net_nehmer_static_interface extends midcom_baseclasses_components_interface
 * {
 *     public function _on_reindex($topic, $config, &$indexer)
 *     {
 *         $qb = midcom::get()->dbfactory->new_query_builder('midcom_db_article');
 *         $qb->add_constraint('topic', '=', $topic->id);
 *         $articles = $qb->execute();
 *
 *         $datamanager = datamanager::from_schemadb($config->get('schemadb'));
 *
 *         foreach ($articles as $article) {
 *             try {
 *                 $datamanager->set_storage($article);
 *             } catch (midcom_error $e) {
 *                 $e->log();
 *                 continue;
 *             }
 *
 *             $document = $indexer->new_document($datamanager);
 *             $document->topic_guid = $topic->guid;
 *             $document->topic_url = $node[MIDCOM_NAV_FULLURL];
 *             $document->read_metadata_from_object($datamanager->storage->object);
 *             $document->component = $topic->component;
 *             $indexer->index($document);
 *         }
 *     }
 * }
 * </code>
 *
 * @package midcom.baseclasses
 * @see midcom_helper__componentloader
 * @see midcom_core_manifest
 */
abstract class midcom_baseclasses_components_interface extends midcom_baseclasses_components_base
{
    /**
     * Class suffix used when constructing the NAP handler class.
     * It is appended to the component class prefix, f.x. resulting in
     * net_nehmer_static_navigation (as a default).
     *
     * @var string
     */
    protected $_nap_class_suffix = 'navigation';

    /**
     * Class suffix used when constructing the on-site handler class.
     * It is appended to the component class prefix, f.x. resulting in
     * net_nehmer_static_viewer (as a default).
     *
     * @var string
     */
    protected $_site_class_suffix = 'viewer';

    // END OF COMPONENT STATE VARIABLES

    // ===================== INITIALIZATION (startup) INTERFACE ======================

    /**
     * Initializes the component.
     *
     * This should <i>not</i> be overwritten by the client. Instead, use the on_initialize
     * event handler.
     *
     * @return boolean Indicating successful initialization.
     * @see _on_initialize()
     */
    public function initialize($component)
    {
        // Preparation
        $this->_component = $component;

        // Call the event handler.
        return $this->_on_initialize();
    }

    // ===================== COMPONENT INTERFACE ======================

    /**
     * This variable holds the context-specific data during processing.
     * It is indexed first by context ID and second by a string key. Currently
     * defined keys are:
     *
     * - <i>config</i> holds the configuration for this context
     * - <i>handler</i> The class handling the request.
     */
    public $_context_data = [];

    /**
     * Configures the component for usage. The configuration is merged, and,
     * if necessary, an existing handler object is purged.
     *
     * @param mixed $configuration A configuration data list, suitable for merging with a
     *     midcom_helper_configuration object.
     * @param int $contextid The ID of the context we are associated with.
     */
    public function configure($configuration, $contextid)
    {
        // Initialize the context data
        $this->_context_data[$contextid] = [
            'config' => $this->_config,
            'handler' => null
        ];

        $this->_context_data[$contextid]['config']->store($configuration, false);
    }

    /**
     * Relays the can_handle call to the component, instantiating a new site
     * class. It will execute can_handle of that class, returning its result
     * to MidCOM.
     *
     * @param midcom_db_topic $current_object The topic in question.
     * @param array $argv The argument listing
     * @param int $contextid The id of the context we are operating in.
     * @return boolean True, if the component can handle the request, false otherwise.
     */
    public function can_handle($current_object, array $argv, $contextid)
    {
        $data =& $this->_context_data[$contextid];
        $loader = midcom::get()->componentloader;
        $class = $loader->path_to_prefix($this->_component) . '_' . $this->_site_class_suffix;
        $data['handler'] = new $class($current_object, $data['config']);
        if (is_a($data['handler'], midcom_baseclasses_components_request::class)) {
            $data['handler']->initialize($this->_component);
        }
        return $data['handler']->can_handle($argv);
    }

    /**
     * Relays the handle call to the component.
     *
     * @return midcom_response|boolean True, if the component successfully handle the request, false otherwise.
     */
    public function handle()
    {
        return $this->_context_data[midcom_core_context::get()->id]['handler']->handle();
    }

    /**
     * Relays the show content call to the component, invoking output.
     *
     * @param int $contextid The id of the context we are operating in.
     */
    public function show_content($contextid)
    {
        $this->_context_data[$contextid]['handler']->show();
    }

    // ===================== NAP INTERFACE ======================

    /**
     * The NAP interface instance from the component, initialized on demand.
     *
     * @var midcom_baseclasses_components_navigation
     */
    private $_nap_instance = null;

    /**
     * Checks, whether an instance of the NAP interface class has already been created
     * and creates it if not.
     *
     * This check is only done during the set_object calls, which will always be the
     * first calls in a sequence of NAP calls. (For performance reasons.)
     */
    private function _check_nap_instance()
    {
        if (is_null($this->_nap_instance)) {
            $loader = midcom::get()->componentloader;
            $class = $loader->path_to_prefix($this->_component) . "_{$this->_nap_class_suffix}";
            $this->_nap_instance = new $class();
            if (is_a($this->_nap_instance, midcom_baseclasses_components_navigation::class)) {
                $this->_nap_instance->initialize($this->_component);
            }
        }
    }

    /**
     * Relays the set_object call to the nap instance. Checks if the NAP instance has already
     * been created beforehand.
     *
     * @param midcom_db_topic $object The midcom_db_topic that should be processed.
     * @return boolean Indicating success.
     */
    public function set_object($object)
    {
        $this->_check_nap_instance();
        return $this->_nap_instance->set_object($object);
    }

    /**
     * Relays the get_node call to the NAP instance.
     *
     * @return Array A NAP compliant NODE structure.
     */
    public function get_node()
    {
        $this->_check_nap_instance();
        return $this->_nap_instance->get_node();
    }

    /**
     * Relays the get_leaves call to the NAP instance.
     *
     * @return Array An Array of NAP compliant leaf structures.
     */
    public function get_leaves()
    {
        $this->_check_nap_instance();
        return $this->_nap_instance->get_leaves();
    }

    /**
     * Returns the currently selected leaf of the request.
     *
     * @return int The active leaf ID out of the component data storage.
     */
    public function get_current_leaf()
    {
        return midcom_baseclasses_components_configuration::get($this->_component, 'active_leaf');
    }

    // ===================== COMMAND EXECUTION HANDLER ======================

    /**
     * Initiate a reindex run for the given component and topic.
     *
     * @param midcom_db_topic $topic The topic that should be reindexed.
     * @return boolean Indicating success.
     * @see _on_reindex()
     */
    public function reindex($topic)
    {
        return $this->_on_reindex($topic, $this->get_config_for_topic($topic), midcom::get()->indexer);
    }

    /**
     * Get the full configuration set active for a given topic.
     * If no topic is passed, the system wide default configuration is returned.
     *
     * Be aware, that this call does not check if the passed topic is actually handled by
     * this component, as it is theoretically possible for components to drop configuration
     * information on other topics as well.
     *
     * @param midcom_db_topic $topic The topic which should be queried, omit to
     *     get the systemwide defaults.
     * @return midcom_helper_configuration MidCOM configuration object
     */
    public function get_config_for_topic($topic = null)
    {
        if ($topic !== null) {
            $this->_config->store_from_object($topic, $this->_component);
        }
        return $this->_config;
    }

    /**
     * Delegate all watched operations, in two phases. First,
     * the general _on_watched_operation handler is called, to allow for handling
     * generic operations. After that, the individual watches are called, to allow
     * for more specific processing.
     *
     * @param int $operation The operation that has occurred.
     * @param mixed $object The object on which the operation occurred. The system will
     *     do is_a checks against any registered class restriction on the watch.
     */
    public function trigger_watch($operation, $object)
    {
        debug_add("We were notified about a watch for the operation {$operation} on " . get_class($object) . " {$object->guid}");

        $this->_on_watched_operation($operation, $object);

        switch ($operation) {
            case MIDCOM_OPERATION_DBA_CREATE:
                $this->_on_watched_dba_create($object);
                break;

            case MIDCOM_OPERATION_DBA_UPDATE:
                $this->_on_watched_dba_update($object);
                break;

            case MIDCOM_OPERATION_DBA_DELETE:
                $this->_on_watched_dba_delete($object);
                break;

            case MIDCOM_OPERATION_DBA_IMPORT:
                $this->_on_watched_dba_import($object);
                break;
        }
    }

    // ===================== START OF THE EVENT HANDLERS =====================

    /**
     * This function is triggered at the end of the request for each watched operation
     * that has been done during the request.
     *
     * It will be called once per operation and unique object, where object uniqueness
     * is determined by the combination of object class and guid. The object has been
     * refreshed before being passed to this event handler.
     *
     * @param int $operation The operation identifier (one of the MIDCOM_OPERATION constants)
     *     which applies.
     * @param object $object The object on which the operation has occurred.
     */
    public function _on_watched_operation($operation, $object)
    {
    }

    /**
     * This function is triggered at the end of the request for each watched create operation
     * that has been done during the request.
     *
     * It will be called once per operation and unique object, where object uniqueness
     * is determined by the combination of object class and guid. The object has been
     * refreshed before being passed to this event handler.
     *
     * It is called after the generic _on_watched_operation event handler.
     *
     * @param object $object The object on which the operation has occurred.
     */
    public function _on_watched_dba_create($object)
    {
    }

    /**
     * This function is triggered at the end of the request for each watched update operation
     * that has been done during the request.
     *
     * It will be called once per operation and unique object, where object uniqueness
     * is determined by the combination of object class and guid. The object has been
     * refreshed before being passed to this event handler.
     *
     * It is called after the generic _on_watched_operation event handler.
     *
     * @param object $object The object on which the operation has occurred.
     */
    public function _on_watched_dba_update($object)
    {
    }

    /**
     * This function is triggered at the end of the request for each watched delete operation
     * that has been done during the request.
     *
     * It is called after the generic _on_watched_operation event handler.
     *
     * @param object $object The object on which the operation has occurred.
     */
    public function _on_watched_dba_delete($object)
    {
    }

    /**
     * This function is triggered at the end of the request for each watched import operation
     * that has been done during the request.
     *
     * It is called after the generic _on_watched_operation event handler.
     *
     * @param object $object The object on which the operation has occurred.
     */
    public function _on_watched_dba_import($object)
    {
    }

    /**
     * This is an event handler, called after the basic component initialization has been done
     * just before the initialize call will return to MidCOM.
     * It should prepare all necessary information to start processing requests.
     *
     * Unless you need more functionality than snippet and library loading, configuration merging
     * and basic component data storage initialization, no further modification needs to be done.
     *
     * @return boolean Indicating whether the initialization has been successful.
     */
    public function _on_initialize()
    {
        return true;
    }

    /**
     * Reindex the given topic. The complete configuration set is already available in
     * $config. The original index records are already deleted, so you do not need to
     * bother about this.
     *
     * The default event handler does nothing.
     *
     * @param midcom_db_topic $topic The topic to reindex.
     * @param midcom_helper_configuration $config The configuration associated with this topic.
     * @param midcom_services_indexer &$indexer The indexer object to use for indexing. (Passed by reference!)
     * @return boolean Indicating success.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        return true;
    }

    // END OF THE EVENT HANDLERS
}
