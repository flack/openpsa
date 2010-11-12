<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interface.php 25323 2010-03-18 15:54:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Baseclass to use for the component interface in MIDCOM.
 *
 * The interface presented here supersedes all original component interface
 * classes (COMPONENT, CONTENTADMIN, MIDCOM and NAP). The class uses an event
 * based approach for subclasses to influence the default behavior.
 *
 * The actual implementation should be enough for most smaller components, as
 * the classes behavior is widely configurable. You should not override any of
 * the base classes interface methods if you can avoid it. If you find that an
 * event handler is missing, please contact the MidCOM development team for
 * some advise.
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
 * - You need to set the values of all <i>Component configuration variables</i>
 *   to something suitable to your component. Especially: $_autoload_files,
 *   $_autoload_libraries and $_component.
 *   The defaults of the other variables should be suitable for basic operation.
 * - The components data storage area will contain two keys when the initialized
 *   event handler is called: The NAP active id, defaulting to false and stored
 *   as <i>active_leaf</i> and the components' default configuration, stored as
 *   a midcom_helper_configuration object in the key <i>$_config_snippet_name</i>.
 *   The active leaf check now automatically returns the contents of the component
 *   data storage area, the components get_active_leaf NAP function is no longer
 *   called.
 * - Put your component wide default configuration into $component_dir/config/config.inc.
 *
 * <b>Class parameters</b>
 *
 *
 * The following options can be used to parametrize the components startup and operation.
 * See the individual member documentation for now.
 *
 * - $_autoload_files
 * - $_autoload_libraries
 * - $_component
 * - $_config_snippet_name
 * - $_nap_class_suffix
 * - $_site_class_suffix
 *
 * <b>Class operation</b>
 *
 * This class now does an awful lot of work for you, you just need to configure it
 * to have the right names and places to look for. It is designed to fit in the
 * current component wildlife with as little collateral damage as possible, but as
 * always, a 100% transparent implementation is both not wanted and not sensible.
 *
 * The most important change is that all four original component interface concepts
 * have been unified into a single class supporting the full interface. Obviously,
 * MidCOM's component loader had to be adapted to this operation.
 *
 * At this time, the core will not be able to handle the original pre-2.4 interface
 * any longer. Though it would be possible to implement an according proxy class, I
 * have not yet done that, so right now all components need to be adapted to the new
 * interface.
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
 * When inheriting the class, you, obviously, start with the constructor. First, as always,
 * call the base class constructor. After that, set all configuration variables outlined
 * below to values suitable to your needs.
 *
 * Usually you will be done at this point, as all other interaction can safely be done by
 * the component. Unless, of course, you have some special requirements.
 *
 * One thing, which I have seen quite often in components, is the initialize method loading
 * the default schema. While I actually do not endorse this behavior, schemas should only
 * be loaded on demand during runtime (especially since they are configurable), you should
 * now add code like that to the _on_initialize() event handler, which will be executed
 * at about the same time as the original implementation would have been.
 *
 * <b>Advanced notes for Core Developers</b>
 *
 * The biggest change relevant for the core is the fact, that the various interface
 * directives for on-site and content-admin usage have changed (they were named equally in
 * both of the original concepts). Therefore, if you handle components directly, you need
 * to adapt your code to match the new interface outlined below.
 *
 * <b>Example usage</b>
 *
 * The average component will require something like this, part one is the component
 * Manifest:
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
 * 'class_definitions' => Array('my_sepcial_mgd_schema_class.inc'),
 * </code>
 *
 * See the class midcom_core_manifest for further details.
 *
 * Built on this, we add the following interface class:
 *
 * <code>
 * class net_nehmer_static_interface extends midcom_baseclasses_components_interface
 * {
 *     function __construct()
 *     {
 *         parent::__construct();
 *
 *         $this->_component = 'net.nehmer.static';
 *         $this->_autoload_files = Array('my_special_mgd_schema_class.php');
 *         $this->_autoload_libraries = Array('midcom.helper.datamanager2');
 *     }
 *
 *     function _on_reindex($topic, $config, &$indexer)
 *     {
 *         $qb = $_MIDCOM->dbfactory->new_query_builder('midcom_db_article');
 *         $qb->add_constraint('topic', '=', $topic->id);
 *         $result = $_MIDCOM->dbfactory->exec_query_builder($qb);
 *
 *         if ($result === false)
 *         {
 *             debug_add("Could not query the articles for {$topic->id}, skipping indexing.");
 *         }
 *
 *         $datamanager = new midcom_helper_datamanager2_datamanager($config->get('schemadb'));
 *         if (! $datamanager)
 *         {
 *             debug_add('Warning, failed to create a datamanager instance with this schemapath:' . $this->_config->get('schemadb'),
 *                MIDCOM_LOG_WARN);
 *             continue;
 *          }
 *
 *         foreach ($articles as $article)
 *         {
 *             if (!$datamanager->autoset_storage($article))
 *             {
 *                 debug_add("Warning, failed to initialize datamanager2 for Article {$article->guid}. See Debug Log for details.", MIDCOM_LOG_WARN);
 *                 debug_print_r('Article dump:', $article);
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
 *
 *     function _on_resolve_permalink($topic, $config, $guid)
 *     {
 *         $article = new midcom_db_article($guid);
 *         if (   ! $article
 *             || $article->topic != $topic->id)
 *         {
 *             return null;
 *         }
 *         if ($article->name == 'index')
 *         {
 *             return '';
 *         }
 *
 *         return "{$article->name}/";
 *     }
 * }
 * </code>
 *
 * @package midcom.baseclasses
 * @see $GLOBALS['midcom_component_data']
 * @see midcom_helper__componentloader
 * @see midcom_core_manifest
 * @todo Document class parameters
 */

class midcom_baseclasses_components_interface
{
    // START OF CONFIGURATION VARIABLES
    /**#@+
     * Component configuration variable, must set during the
     * construction of the inherited classes.
     *
     * @access protected
     */

    /**
     * The name of the component, e.g. net.nehmer.static
     *
     * @var string
     */
    var $_component = '';

    /**
     * A list of files, relative to the components root directory, that
     * should be loaded during initialization.
     *
     * @var Array
     */
    var $_autoload_files = Array();

    /**
     * A list of libraries which should by loaded during initialization.
     * This will be done before actually loading the script files from
     * _autoload_files.
     *
     * @var Array
     */
    var $_autoload_libraries = Array();

    /**
     * This is used during initialization when loading the default configurations
     * from the filesystem ($prefix/config/$name.inc) and the snippetdirs
     * ($GLOBALS['midcom_config']['midcom_sgconfig_basedir']/$component/$name).
     * They will be merged and placed into the
     * component data store under a key with the same name then the snippet as
     * a midcom_helper_configuration object.
     *
     * Set this to null to disable automatic configuration handling.
     *
     * @var string
     */
    var $_config_snippet_name = 'config';

    /**
     * This is the class suffix used when constructing the NAP handler class.
     * It is appended to the component class prefix, f.x. resulting in
     * net_nehmer_static_navigation (as a default).
     *
     * @var string
     */
    var $_nap_class_suffix = 'navigation';

    /**
     * This is the class suffix used when constructing the on-site handler class.
     * It is appended to the component class prefix, f.x. resulting in
     * net_nehmer_static_viewer (as a default).
     *
     * @var string
     */
    var $_site_class_suffix = 'viewer';

    // END OF CONFOIGURATION VARIABLES
    /**#@-*/

    // START OF COMPONENT STATE VARIABLES
    /**#@+
     * Current object state, useful during initialization and automatically
     * populated before _on_initialization is called.
     *
     * @access protected
     */

    /**
     * The full path to the components' root directory. Used for loading files.
     *
     * @var string
     */
    var $_component_path = '';

    /**
     * The component-specific data storage, hold in the global Array
     * $midcom_component_data, which is indexed by the component name.
     *
     * It is created during initialization, and a reference to the actual
     * storage is put into $_data.
     *
     * @var Array
     * @see $GLOBALS['midcom_component_data']
     */
    var $_data = null;

    /**
     * The component manifest instance associated with this component. Read-Only and automatically
     * populated during initialization.
     *
     * @var midcom_core_manifest
     */
    var $_manifest = null;

    // END OF COMPONENT STATE VARIABLES

    /**
     * Initialize the class, nothing to do yet.
     */
    public function __construct()
    {
        // Nothing to do yet.
    }

    // ===================== INITIALIZATION (startup) INTERFACE ======================

    /**
     * Initializes the component. It will first load all dependent libraries and
     * then include the snippets referenced by the component. The components local
     * data storage area is initialized and referenced into the global storage area.
     * Finally, the on_init event handler is called.
     *
     * This should <i>not</i> be overwritten by the client. Instead, use the on_initialize
     * event handler.
     *
     * @return boolean Indicating successful initialization.
     * @see _on_initialize()
     */
    public function initialize()
    {
        // Preparation
        $loader = $_MIDCOM->get_component_loader();
        $this->_component_path = MIDCOM_ROOT . $loader->path_to_snippetpath($this->_component);
        $this->_manifest = $_MIDCOM->componentloader->manifests[$this->_component];

        // Load libraries
        foreach ($this->_autoload_libraries as $library)
        {
            if (! $_MIDCOM->load_library($library))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to load library {$library} while initializing {$this->_component}");
                // This will exit.
            }
        }

        // Load scripts
        foreach ($this->_autoload_files as $file)
        {
            require_once("{$this->_component_path}/{$file}");
        }

        // Initialize the global data storage
        $GLOBALS['midcom_component_data'][$this->_component] = Array();
        $this->_data =& $GLOBALS['midcom_component_data'][$this->_component];
        $this->_data['active_leaf'] = false;

        // Process configuration
        $this->_load_configuration();

        // Call the event handler.
        return $this->_on_initialize();
    }

    /**
     * Loads the configuration file specified by the component configuration
     * and constructs a midcom_helper_configuration object out of it. Both
     * Component defaults and sitegroup-configuration gets merged. The
     * resulting object is stored under the key 'config' in the
     * components' data storage area.
     *
     * Errors will be logged as MIDCOM_LOG_WARN but silently ignored. This
     * should be viable, since as of MidCOM 2.4 the configuration class is
     * more flexible when local and global configurations do not match.
     *
     * Three files will be loaded in order:
     *
     * 1. The components default configuration, placed in $prefix/config/$name.inc
     * 2. Any systemwide default configuration, currently placed in /etc/midgard/midcom/$component/$name.inc.
     * 3. Any site configuration in the snippet $GLOBALS['midcom_config']['midcom_sgconfig_basedir']/$component/$name.
     *
     * If $_config_snippet_name is set to null, no configuration will be done.
     *
     * @access protected
     * @see midcom_helper_configuration
     * @see $_config_snippet_name
     */
    private function _load_configuration()
    {
        if (is_null($this->_config_snippet_name))
        {
            // Nothing to do.
            return;
        }

        // Load and parse the global config
        $data = $this->read_array_from_file("{$this->_component_path}/config/{$this->_config_snippet_name}.inc");
        if (! $data)
        {
            // Empty defaults
            $data = Array();
        }
        $config = new midcom_helper_configuration($data);

        // Go for the sitewide default
        $data = $this->read_array_from_file("/etc/midgard/midcom/{$this->_component}/{$this->_config_snippet_name}.inc");
        if ($data !== false)
        {
            $config->store($data, false);
        }

        // Finally, check the sitegroup config
        $data = $this->read_array_from_snippet("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$this->_component}/{$this->_config_snippet_name}");
        if ($data !== false)
        {
            $config->store($data, false);
        }

        $this->_data['config'] = new midcom_helper_configuration($config->get_all());
    }

    /**
     * This helper function reads a file from disk and evaluates its content as array.
     * This is essentially a simple Array($data\n) eval construct.
     *
     * If the file does not exist, false is returned.
     *
     * This function may be called statically.
     *
     * @param string $filename The name of the file that should be parsed.
     * @return Array The read data or false on failure.
     * @see read_array_from_snippet()
     */
    public function read_array_from_file($filename)
    {
        if (!file_exists($filename))
        {
            return array();
        }

        try
        {
            $data = file_get_contents($filename);
        }
        catch (Exception $e)
        {
            return false;
        }
        $result = eval("\$data = array({$data}\n);");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse content loaded from file '{$filename}', see above for PHP errors.");
            // This will exit.
        }
        return $data;
    }

    /**
     * This helper function reads a snippet and evaluates its content as array.
     * This is essentially a simple Array($data\n) eval construct.
     *
     * If the snippet does not exist, false is returned.
     *
     * This function may be called statically.
     *
     * @param string $snippetpath The full path to the snippet that should be returned.
     * @return Array The read data or false on failure.
     * @see read_array_from_file()
     */
    public function read_array_from_snippet($snippetpath)
    {
        $code = midcom_get_snippet_content_graceful($snippetpath);
        $result = eval("\$data = Array({$code}\n);");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse content loaded from snippet '{$snippetpath}', see above for PHP errors.");
            // This will exit.
        }
        return $data;
    }

    // ===================== COMPONENT (on-site) and CONTENTADM INTERFACE ======================

    /**
     * This variable holds the context-specific data during processing.
     * it is indexed first by context ID and second by a string key. Currently
     * defined keys are:
     *
     * - <i>config</i> holds the configuration for this context
     * - <i>admin</i> A flag indicating whether we are in Admin mode or not (set during configure)
     * - <i>handler</i> The class handling the request.
     */
    public $_context_data = Array();

    /**
     * Configures the component for usage. The configuration is merged, and,
     * if necessary, an existing handler object is purged.
     *
     * @param mixed $configuration A configuration data list, suitable for merging with a
     *     midcom_helper_configuration object.
     * @param int $contextid The ID of the context we are associated with.
     * @return boolean Indication success.
     */
    public function configure($configuration, $contextid)
    {
        // Initialize the context data
        $this->_context_data[$contextid] = Array
        (
            'config' => null,
            'handler' => null
        );

        $data =& $this->_context_data[$contextid];

        if (is_null($this->_data['config']))
        {
            $data['config'] = new midcom_helper_configuration($this->_component);
        }
        else
        {
            $data['config'] = $this->_data['config'];
        }
        if (! $data['config']->store($configuration))
        {
            return false;
        }

        $data['handler'] = null;

        return true;
    }

    /**
     * Relays the can_handle call to the component, instantiating a new Site
     * class. It will execute can_handle of that class, returning its result
     * to MidCOM.
     *
     * @param midcom_db_topic $current_object The topic in question.
     * @param int $argc The count of the remaining URL arguments.
     * @param Array $argv The argument listing
     * @param int $contextid The id of the context we are operating in.
     * @return boolean True, if the component can handle the request, false otherwise.
     */
    public function can_handle($current_object, $argc, $argv, $contextid)
    {
        $data =& $this->_context_data[$contextid];
        $loader = $_MIDCOM->get_component_loader();
        $class = $loader->path_to_prefix($this->_component) . '_' . $this->_site_class_suffix;
        $data['handler'] = new $class($current_object, $data['config']);
        if (is_a($data['handler'], 'midcom_baseclasses_components_request'))
        {
            $data['handler']->initialize($this->_component);
        }
        return $data['handler']->can_handle($argc, $argv);
    }

    /**
     * Relays the handle call to the component.
     *
     * @param midcom_db_topic $current_object The topic in question.
     * @param int $argc The count of the remaining URL arguments.
     * @param Array $argv The argument listing
     * @param int $contextid The id of the context we are operating in.
     * @return boolean True, if the component successfully handle the request, false otherwise.
     */
    public function handle($current_object, $argc, $argv, $contextid)
    {
        return $this->_context_data[$contextid]['handler']->handle($argc, $argv);
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
     * @var object
     * @access private
     */
    private $_nap_instance = null;

    /**
     * Checks, whether an instance of the NAP interface class has already been created
     * and creates it if not.
     *
     * This check is only done during the set_object calls, which will always be the
     * first calls in a sequence of NAP calls. (For performance reasons.)
     *
     * @access private
     */
    private function _check_nap_instance()
    {
        if (is_null($this->_nap_instance))
        {
            $loader = $_MIDCOM->get_component_loader();
            $class = $loader->path_to_prefix($this->_component) . "_{$this->_nap_class_suffix}";
            $this->_nap_instance = new $class();
            if (is_a($this->_nap_instance, 'midcom_baseclasses_components_navigation'))
            {
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
     * Originally, this was relayed to the NAP instance. With the new
     * component data framework, the NAP active leaf ID can be returned
     * directly using the data key <i>active_leaf</i>.
     *
     * @return int The active leaf ID out of the component data storage.
     */
    public function get_current_leaf()
    {
        return $this->_data['active_leaf'];
    }


    // ===================== COMMAND EXECUTION HANDLER ======================

    /**
     * This new interface function will initiate a reindex run for the given
     * component and topic. See the _on_reindex() event handler for details.
     *
     * @param midcom_db_topic $topic The topic that should be reindexed.
     * @return boolean Indicating success.
     * @see _on_reindex()
     */
    public function reindex ($topic)
    {
        return $this->_on_reindex($topic, $this->get_config_for_topic($topic), $_MIDCOM->get_service('indexer'));
    }

    /**
     * Verify an indexer document's permissions.
     *
     * It will call the corresponding event handler reading the topic configuration beforehand.
     *
     * @param midcom_services_indexer_document &$document The document to check. This object is passed by
     *     reference and may therefore be modified to match the current security policy.
     * @param midcom_db_topic $topic The topic this document is assigned to.
     * @return boolean True if the object may be shown, false otherwise.
     */
    public function check_document_permissions (&$document, $topic)
    {
        return $this->_on_check_document_permissions($document, $this->get_config_for_topic($topic), $topic);
    }

    /**
     * Retrieve all members of a given virtual group. This information may be cached by the framework.
     *
     * Executio0n is relayed to the corresponding event handler.
     *
     * @param string $groupname The local groupname (that is, without the component prefix) of the virtual group to query.
     * @return Array Associative user->id => user_object listing of all member users or null on failure.
     */
    public function retrieve_vgroup_members ($groupname)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $members = $this->_on_retrieve_vgroup_members ($groupname);

        if (is_null($members))
        {
            debug_add("The members of the virtual group {$groupname} within {$this->_component} could not be loaded, eventhandler failed to execute.", MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }

        $result = Array();
        foreach ($members as $orig_member)
        {
            if (   ! is_object($orig_member)
                || ! is_a($orig_member, 'midcom_core_user'))
            {
                $member = $_MIDCOM->auth->get_user($orig_member);
            }

            if (! is_a($member, 'midcom_core_user'))
            {
                // Something went wrong.
                debug_add('Could not cast a member element to a midcom_core_user object, see debug level log for details.', MIDCOM_LOG_INFO);
                debug_print_r('Passed member object was:', $orig_member);
                continue;
            }

            $result[$member->id] = $member;
        }

        debug_pop();
        return $result;
    }

    /**
     * This interface function is used to check whether a component can handle a given GUID
     * or not <i>on site only.</i> A topic is provided which limits the "scope" of the search
     * accordingly. It can be safely assumed that the topic given is a valid topic in the
     * MidCOM content tree (it is checked through NAP).
     *
     * If the guid could be successfully resolved, a URL local to the given topic without a
     * leading slash must be returned (f.x. 'article/'), empty strings ('') are allowed
     * indicating root page access. If the GUID is invalid, null will be returned.
     *
     * This call is relayed to the component using the event handler _on_resolve_permalink().
     * Before that it will deduce the active configuration for the given topic.
     *
     * Note, that this is the only event handler which has some kind of default implementation,
     * see its documentation for details.
     *
     * @param string $guid The permalink GUID that should be looked up.
     * @param midcom_db_topic $topic the Topic to look up.
     * @return string The local URL (without leading slashes) or null on failure.
     * @see _on_resolve_permalink()
     */
    public function resolve_permalink ($topic, $guid)
    {
        return $this->_on_resolve_permalink($topic, $this->get_config_for_topic($topic), $guid);
    }

    /**
     * This is a small helper function which gets the full configuration set active for a given
     * topic. If no topic is passed, the systemwide default configuration is returned.
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
        $config = $this->_data['config'];
        if ($topic !== null)
        {
            $config->store_from_object($topic, $this->_component);
        }
        return $config;
    }

    /**
     * This function delegates all watched operations, in two phases. First,
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
        debug_push_class($this, __FUNCTION__);
        debug_add("We were notified about a watch for the operation {$operation} on " . get_class($object) . " {$object->guid}");
        debug_pop();

        $this->_on_watched_operation($operation, $object);

        switch ($operation)
        {
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

    /**#@+
     * This is an event handler which is called during MidCOM's component interaction.
     * For most basic components, the default implementation should actually be enough.
     *
     * @access protected
     */

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
    function _on_watched_operation($operation, $object) {}

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
    function _on_watched_dba_create($object) {}

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
    function _on_watched_dba_update($object) {}

    /**
     * This function is triggered at the end of the request for each watched delete operation
     * that has been done during the request.
     *
     * It is called after the generic _on_watched_operation event handler.
     *
     * @param object $object The object on which the operation has occurred.
     */
    function _on_watched_dba_delete($object) {}

    /**
     * This function is triggered at the end of the request for each watched import operation
     * that has been done during the request.
     *
     * It is called after the generic _on_watched_operation event handler.
     *
     * @param object $object The object on which the operation has occurred.
     */
    function _on_watched_dba_import($object) {}

    /**
     * This is an event handler, called after the basic component initialization has been done
     * just before the initialize call will return to MidCOM.
     * It should prepare all necessary information to start processing requests.
     *
     * Unless you need more functionality then snippet and library loading, configuration merging
     * and basic component data storage initialization, no further modification needs to be done.
     *
     * @return boolean Indicating whether the initialization has been successful.
     */
    function _on_initialize()
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
     * @param midgard_helper_config $config The configuration associated with this topic.
     * @param midcom_services_indexer The indexer object to use for indexing. (Passed by reference!)
     * @return boolean Indicating success.
     */
    function _on_reindex($topic, $config, &$indexer)
    {
        return true;
    }

    /**
     * Verify an indexer document's permissions. This is used for custom, advanced access control
     * within a components domain.
     *
     * The topic and configuration objects are passed for ease of use and performance, as they have already
     * been prepared by the framework.
     *
     * Usually, you want to limit the visibility of a
     * document in the search result. You can do this by returning false in this function, the indexer
     * will then skip this object before returning the resultset to the callee.
     * You may modify the document that has been passed, to limit the information available to the client,
     * though this <i>should</i> be avoided if possible.
     *
     * @param midcom_services_indexer_document &$document The document to check. This object is passed by
     *     reference and may therefore be modified to match the current security policy.
     * @param midcom_helper_configuration $config The configuration associated with the topic.
     * @param MidgardTopic $topic The topic this document is assigned to.
     * @return boolean True if the object may be shown, false otherwise.
     */
    function _on_check_document_permissions (&$document, $config, $topic)
    {
        return true;
    }

    /**
     * Retrieve all members of a given virtual group. This information may be cached by the framework.
     *
     * @param string $groupname The local groupname (that is, without the component prefix) of the virtual group to query.
     * @return Array List of members. You may add either one of the following types to this array: Person IDs, Person GUIDs,
     *     midcom_core_baseclasses_person objects (or derived classes), MgdSchema or legacy Person objects, midcom_core_user
     *     objects.
     */
    function _on_retrieve_vgroup_members ($groupname)
    {
        return null;
    }

    /**
     * This interface function is used to check whether a component can handle a given GUID
     * or not <i>on site only.</i> A topic is provided which limits the "scope" of the search
     * accordingly. It can be safely assumed that the topic given is a valid topic in the
     * MidCOM content tree (it is checked through NAP).
     *
     * If the guid could be successfully resolved, a URL local to the given topic without a
     * leading slash must be returned (f.x. 'article/'), empty strings ('') are allowed
     * indicating root page access. If the GUID is invalid, null will be returned.
     *
     * This call is relayed to the component using the event handler _on_resolve_permalink().
     * Before that it will deduce the active configuration for the given topic.
     *
     * The information you return with this call (if no-null) will be considered cacheable by
     * the content caching engine. Therefore you have to ensure that either the resolution
     * is stable or that you configure the content cache accordingly if you have a match.
     * The hard way is setting the no_cache flag in cases where you need full flexibility, but
     * this should be avoided for the sake of performance if somehow possible. The more
     * sophisticated alternative is therefore to selectively invalidate all GUIDs that have
     * their Permalink lookup affected.
     *
     * <b>Important Note:</b>
     *
     * Be aware that this is the only event handler at this time which has a real default
     * implementation: If you do not override the base class implementation, it will iterate
     * through all NAP leaves applicable to the node associated with the topic. If a match
     * is found, its local URL will be returned. This will not be terribly efficient, so
     * you are strongly encouraged to have some more efficient solution instead. Obviously,
     * if you override the function, you shouldn't call the base class implementation unless
     * you really need it.
     *
     * @param string $guid The permalink GUID that should be looked up.
     * @param midcom_db_topic $topic the Topic to look up.
     * @param midcom_helper_configuration $config The configuration used for the given topic.
     * @return string The local URL (without leading slashes) or null on failure.
     */
    function _on_resolve_permalink($topic, $config, $guid)
    {
        $nav = new midcom_helper_nav();
        $leaves = $nav->list_leaves($topic->id);
        if (! $leaves)
        {
            return null;
        }
        foreach ($leaves as $leafid)
        {
            $leaf = $nav->get_leaf($leafid);
            if ($leaf[MIDCOM_NAV_GUID] == $guid)
            {
                return $leaf[MIDCOM_NAV_URL];
            }
        }
        return null;
    }

    // END OF THE EVENT HANDLERS
    /**#@-*/
}
?>