<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: manifest.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class encapsulates a the information contained in a component manifest.
 * Naturally, its information is read-only.
 *
 * <b>The Component Manifest</b>
 *
 * The Manifest contains all necessary information to deal with a component without
 * actually having to load them. Originally, all of this information has been included
 * in the component interface class itself, but this provides to be a rather slow
 * alternative. See the <i>History</i> Section below for further information about this
 * background.
 *
 * The component manifest on-disk actually consists of a relatively simple associative
 * array which describes the component. The following example shows an average manifest
 * using all available options and should serve us as an example:
 *
 * <code>
 * 'name' => 'net.nehmer.static',
 * 'purecode' => false,
 * 'version' => 15,
 * 'privileges' => Array
 * (
 *     'read' => MIDCOM_PRIVILEGE_ALLOW,
 *     'write' => Array (MIDCOM_PRIVILEGE_DENY, MIDCOM_PRIVILEGE_ALLOW)
 * ),
 * 'class_definitions' => Array ('my_classes.inc'),
 * 'watches' => Array
 * (
 *     Array
 *     (
 *         'classes' => null,
 *         'operations' => MIDCOM_OPERATION_DBA_UPDATE,
 *     ),
 *     Array
 *     (
 *         'classes' => Array
 *             (
 *                 'midcom_baseclasses_database_article',
 *                 'midcom_baseclasses_database_topic',
 *             ),
 *         'operations' =>
 *               MIDCOM_OPERATION_DBA_CREATE
 *             | MIDCOM_OPERATION_DBA_DELETE,
 *     ),
 * ),
 * 'customdata' => Array
 * (
 *     'componentname' => $config_array
 * ),
 * </code>
 *
 * As you can see, most of these settings mainly match the configuration options you
 * already know from the component interface base class. The information located in this
 * manifest is now used to configure those interface classes automatically, you do not
 * have to manage this information twice.
 *
 * All keys except 'name' are purely optional.
 *
 * <i>string name</i> should be clear, it is the full name of the component.
 *
 * <i>boolean purecode</i> is equally easy, indicating whether this is a library or a full
 * scale component.
 *
 * <i>int version</i> is the internal version number of the interface. This value is
 * currently unused in MidCOM context but can be used by your component to manage automatic
 * data storage upgrades.
 *
 * <i>Array privileges</i> is a more complex thing. It contains the full list of all privileges
 * defined by the component. They are propagated to the ACL service during system startup.
 * You have to add only the local keys, the component prefix is
 * added automatically when the information is registered.
 *
 * This array is an associative one, indexing permission names and their
 * default values. Simple string values are required for the permission
 * names, they must validate against the regular expression <i>/[a-z0-9]+/</i>.
 *
 * So, if you want to add {$component}:read with a default value of
 * MIDCOM_PRIVILEGE_ALLOW you would do something like this:
 *
 * <code>
 * $this->_acl_privileges['read'] => MIDCOM_PRIVILEGE_ALLOW;
 * </code>
 *
 * This assumes, that object owners should get no specific treatment, e.g.
 * that the owner privilege set inherits its value from the content object
 * parent. In case you want to explicitly set a distinct value for an
 * object owner, you must pass an array to this function:
 *
 * <code>
 * $this->_acl_privileges['write'] => Array
 * (
 *     MIDCOM_PRIVILEGE_DENY, // default privilege value
 *     MIDCOM_PRIVILEGE_ALLOW // owner default privilege value
 * )
 * </code>
 *
 * In this case the definition grants object owners the defined privilege. The
 * only way this can be overridden now is having the privilege denied to the
 * accessing user directly (e.g. a person privilege).
 *
 * Note, that it is often sensible to do something like this:
 *
 * <code>
 * $this->_acl_privileges['read'] => Array
 * (
 *     MIDCOM_PRIVILEGE_ALLOW, // default privilege value
 *     MIDCOM_PRIVILEGE_ALLOW // owner default privilege value
 * )
 * </code>
 *
 * That way, object owners can read the object, even if the read access is
 * prohibited for a user's group for example. Without the explicit
 * user default specification it would get inherited from there.
 *
 * So, if you take the very first example from above again (the one without
 * the Array), it is read by MidCOM as if you would have specified this:
 *
 * <code>
 * $this->_acl_privileges['read'] => Array
 * (
 *     MIDCOM_PRIVILEGE_ALLOW, // default privilege value
 *     MIDCOM_PRIVILEGE_INHERIT // owner default privilege value
 * )
 * </code>
 *
 * Note, that INHERIT does not INHERIT from the system default privilege but
 * from the <i>immediate parent</i>.
 *
 * <i>Array class_definitions</i> contains a list of class definition files of the classes
 * the component makes available to the framework. Their intermediate classes are loaded
 * during startup, and the component is loaded dynamically whenever the final DBA
 * implementation is needed.
 *
 * <i>Array watches</i> is a special thing. It allows your component to specify that
 * it wants to "observe" all operations of a certain type on DBA objects. Such a watch
 * declaration consists of two keys, <i>classes</i> and <i>operation</i>. Classes defines
 * for which DBA types (you need to specify the direct DBA types here, not any descendants)
 * you want to watch operations, null indicates an unlimited watch. The operation key
 * then, obviously, specifies the operation(s) you want to watch, which is a bitfield
 * consisting of MIDCOM_OPERATION_xxx flags.
 *
 * <i>Array customdata</i> is the run-of-the-mill extension place of the system. It lets
 * you place arbitrary arrays indexed by components (like 'midcom.services.cron') into 
 * it along with meta-information relevant to that component only. This is used to extend 
 * the information available through the context. No key in here is mandatory, the default 
 * is an empty array.
 *
 * <b>Loading a Component Manifest based on a file on disk</b>
 *
 * The class is always initialized using a component name. It will load the components'
 * manifest from disk, executing any post-processing necessary at that point (like
 * the completion of the privilege names).
 *
 * Usually you should not have to bother with this, as it is managed by the component
 * loader.
 *
 * Be aware, that you must ensure the correctness of the manifest you provide. For
 * performance reasons it is not validated during runtime.
 *
 * <b>Caching</b>
 *
 * The component loader does now maintain a simple cached script which loads the manifests
 * of all installed components. The manifest information themselves are thus not subject
 * to caching, only the list of components.
 *
 * So, essentially, you need to invalidate the MidCOM cache whenever you remove or install
 * components.
 *
 *
 * <b>History</b>
 *
 * Originally, MidCOM did retrieve all necessary information about a component by using
 * the main Component Interface Class, nowadays this is usually a subclass of
 * midcom_baseclasses_components_interface. While this was certainly an easy solution,
 * especially in the beginning where not much information was related to the component,
 * it proved to be anything but scalable.
 *
 * By the time of the implementation of this manifest, many places of the system (particular
 * DBA and ACL) requires all components to be loaded to have access to class names,
 * defined ACL privileges and similar things. This was rather time consuming and unnecessary.
 * Hence the Manifest was introduced to be able to handle components without actually loading
 * their interfaces.
 *
 * @package midcom
 */
class midcom_core_manifest extends midcom_baseclasses_core_object
{
    /**
     * The raw manifest array as loaded from disc.
     *
     * @var string
     * @access private
     */
    var $_raw_data = null;

    /**#@+
     * Component meta-information, read and post-processed from the manifest
     * file.
     *
     * @access public
     */

    /**
     * The name of the component.
     *
     * @var string
     */
    var $name = '';

    /**
     * This is the translated, full component name obtained by looking up the string
     * $name in the l10n library $name. 

     * This member is only populated on demand by the get_translated_name() function.
     */
    var $name_translated = null;

    /**
     * If this is true, it is a pure-code component, otherwise it is a full blown
     * component.
     *
     * @var boolean
     */
    var $purecode = false;

    /**
     * Version number of the components.
     *
     * @var string
     */
    var $version = '0.0.1';

    /**
     * State of the components.
     *
     * @var string
     */
    var $state = 'devel';

    /**
     * Privileges array definition. 
     *
     * Indexes are the full privilege names (including the component
     * prefix), values are arrays holding the global / owner privilege default.
     *
     * @todo Complete documentation
     *
     * @var Array
     */
    var $privileges = Array();

    /**
     * A list of class definition filenames 
     *
     * (all looked up in the components configuration directory).
     *
     * @var array
     */
    var $class_definitions = Array();

    /**
     * A list of all watches defined by the component.
     *
     * @var array
     */
    var $watches = null;

    /**
     * Custom place to extend the schema.
     *
     * The array holds data indexed by the component name
     * they are relevant to.
     *
     * @var array
     */
    var $customdata = Array();
    /**
     * the filename the manifest was loaded from
     */
    var $filename;
    /**#@-*/

    /**
     * The constructor loads the manifest indicated by the filename passed to it.
     *
     * If it is a relative path, it is evaluated against MIDCOM_ROOT. Otherwise,
     * the file is accessed directly.
     *
     * @param string $filename The name of the manifest file to load.
     * @param array $values the values the manifest uses.
     */
    function __construct($filename,$values)
    {
        //parent::__construct();
        $this->filename = $filename;
        $this->_load_manifest($values);

    }

    /**
     * This internal helper loads and evaluates the given manifest file.
     *
     * @param string $filename The name of the manifest file to load.
     * @return boolean True if the manifest was successfully loaded, false otherwise.
     * @access protected
     * @todo move this into the constructor, use isset.
     */
    function _load_manifest($values )
    {
        $this->_raw_data = $values;
        if (!is_array($this->_raw_data))
        {
            debug_add("Manifest read from file {$this->filename} does not evaluate properly", MIDCOM_LOG_ERROR);
        }

        $this->name = $this->_raw_data['name'];

        if (array_key_exists('purecode', $this->_raw_data))
        {
            $this->purecode = ($this->_raw_data['purecode'] == true);
        }
        if (array_key_exists('version', $this->_raw_data))
        {
            $this->version = $this->_raw_data['version'];
        }
        if (array_key_exists('state', $this->_raw_data))
        {
            $this->state = $this->_raw_data['state'];
        }
        if (array_key_exists('privileges', $this->_raw_data))
        {
            $this->_process_privileges();
        }
        if (array_key_exists('class_definitions', $this->_raw_data))
        {
            $this->class_definitions = $this->_raw_data['class_definitions'];
        }
        if (array_key_exists('watches', $this->_raw_data))
        {
            $this->watches = $this->_raw_data['watches'];
        }
        if (array_key_exists('customdata', $this->_raw_data))
        {
            $this->customdata = $this->_raw_data['customdata'];
        }

        return true;
    }

    /**
     * Populates and translates the name of the component.
     *
     * @see $name_translated
     */
    function get_name_translated()
    {
        if ($this->name_translated === null)
        {
            $this->name_translated = $_MIDCOM->i18n->get_string($this->name, $this->name);
        }
        return $this->name_translated;
    }

    /**
     * This is an internal startup helper which extracts and post-processes the privilege
     * definitions in the loaded manifest information.
     *
     * It will not complete any missing owner default privileges, this is done by the 
     * Authentication service upon privilege registering.
     *
     * @access protected
     */
    function _process_privileges()
    {
        foreach ($this->_raw_data['privileges'] as $name => $defaults)
        {
            $this->privileges["{$this->name}:{$name}"] = $defaults;
        }
    }
}

?>