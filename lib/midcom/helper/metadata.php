<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is an interface to the metadata of MidCOM objects.
 *
 * It will use an internal mechanism to cache repeated accesses to the same
 * metadata key during its lifetime. (Invalidating this cache will be possible
 * though.)
 *
 * <b>Metadata Key Reference</b>
 *
 * See the schema in /midcom/config/metadata_default.inc
 *
 * <b>Example Usage, Metadata Retrieval</b>
 *
 * <code>
 * <?php
 * $nap = new midcom_helper_nav();
 * $node = $nap->get_node($nap->get_current_node());
 *
 * $meta = midcom_helper_metadata::retrieve($node[MIDCOM_NAV_GUID]);
 * echo "Visible : " . $meta->is_visible() . "</br>";
 * echo "Approved : " . $meta->is_approved() . "</br>";
 * echo "Keywords: " . $meta->get('keywords') . "</br>";
 * </code>
 *
 * <b>Example Usage, Approval</b>
 *
 * <code>
 * <?php
 * $article = new midcom_db_article($my_article_created_id);
 *
 * $meta = midcom_helper_metadata::retrieve($article);
 * $meta->approve();
 * </code>
 *
 * @property integer $schedulestart The time upon which the object should be made visible. 0 for no restriction.
 * @property integer $scheduleend The time upon which the object should be made invisible. 0 for no restriction.
 * @property boolean $navnoentry Set this to true if you do not want this object to appear in the navigation without it being completely hidden.
 * @property boolean $hide Set this to true to hide the object on-site, overriding scheduling.
 * @property string $keywords The keywords for this object, should be used for META HTML headers.
 * @property string $description A short description for this object, should be used for META HTML headers.
 * @property string $robots Search engine crawler instructions, one of '' (unset), 'noindex', 'index', 'follow' and 'nofollow'.
 *      See the corresponding META HTML header.
 * @property integer $published The publication time of the object, read-only.
 * @property string $publisher The person that published the object (i.e. author), read-only except on articles and pages.
 * @property integer $created The creation time of the object, read-only unless an article is edited.
 * @property string $creator The person that created the object, read-only.
 * @property integer $revised The last-modified time of the object, read-only.
 * @property string $revisor The person that modified the object, read-only.
 * @property integer $approved The time of approval of the object, or 0 if not approved. Set automatically through approve/unapprove.
 * @property string $approver The person that approved/unapproved the object. Set automatically through approve/unapprove.

 * @package midcom.helper
 */
class midcom_helper_metadata
{
    /**
     * Object to which we are attached to. This object can be accessed from
     * the outside, where necessary.
     *
     * @var MidgardObject
     */
    public $__object = null;

    /**
     * Metadata object of the current object
     *
     * @var midgard_metadata
     */
    private $__metadata = null;

    /**
     * The guid of the object
     *
     * @var string GUID
     */
    var $guid = '';

    /**
     * Holds the values already read from the database.
     *
     * @var Array
     */
    private $_cache = Array();

    /**
     * The schema database URL to use for this instance.
     *
     * @var string
     */
    private $_schemadb_path = null;

    /**
     * Datamanager instance for the given object.
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    /**
     * This will construct a new metadata object for an existing content object.
     *
     * You must never use this constructor directly, it is considered private
     * in this respect. Instead, use the retrieve method, which may be called as a
     * class method.
     *
     * You may use objects derived from any MidgardObject will do as well as long
     * as the parameter call is available normally.
     *
     * @param string $guid The GUID of the object
     * @param mixed $object The MidgardObject to attach to.
     * @param string $schemadb The URL of the schemadb to use.
     * @see midcom_helper_metadata::retrieve()
     */
    public function __construct($guid, $object, $schemadb)
    {
        $this->guid = $guid;
        $this->__metadata = $object->__object->metadata;
        $this->__object = $object;
        $this->_schemadb_path = $schemadb;
    }

    /* ------- BASIC METADATA INTERFACE --------- */

    /**
     * Return a single metadata key from the object. The return
     * type depends on the metadata key that is requested (see the class introduction).
     *
     * You will not get the data from the datamanager using this calls, but the only
     * slightly post-processed metadata values. See _retrieve_value for post processing.
     *
     * @see midcom_helper_metdata::_retrieve_value()
     * @param string $key The key to retrieve
     * @return mixed The key's value.
     */
    public function get($key)
    {
        if (!$this->__metadata)
        {
            return null;
        }

        if (!isset($this->_cache[$key]))
        {
            $this->_retrieve_value($key);
        }

        return $this->_cache[$key];
    }

    public function __get($key)
    {
        if ($key == 'object')
        {
            return $this->__object;
        }
        return $this->get($key);
    }

    public function __isset($key)
    {
        if (!$this->__metadata)
        {
            return false;
        }

        if (!isset($this->_cache[$key]))
        {
            $this->_retrieve_value($key);
        }

        return isset($this->_cache[$key]);
    }

    /**
     * Return a Datamanager instance for the current object.
     *
     * This is returned by reference, which must be honored, as usual.
     *
     * Also, whenever the containing datamanager stores its data, you
     * <b>must</b> call the on_update() method of this class. This is
     * very important or backwards compatibility will be broken.
     *
     * @return midcom_helper_datamanager2 A initialized Datamanager instance for the selected object.
     * @see midcom_helper_metadata::on_update()
     */
    function & get_datamanager()
    {
        if (is_null($this->_datamanager))
        {
            $this->load_datamanager();
        }
        return $this->_datamanager;
    }

    /**
     * Loads the datamanager for this instance. This will patch the schema in case we
     * are dealing with an article.
     */
    function load_datamanager()
    {
        static $schemadbs = array();
        if (!array_key_exists($this->_schemadb_path, $schemadbs))
        {
            $schemadbs[$this->_schemadb_path] = midcom_helper_datamanager2_schema::load_database($this->_schemadb_path);
        }
        $this->_schemadb = $schemadbs[$this->_schemadb_path];
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        $object_schema = self::find_schemaname($this->_schemadb, $this->__object);
        $this->_datamanager->set_schema($object_schema);
        if (!$this->_datamanager->set_storage($this->__object))
        {
            throw new midcom_error('Failed to initialize the metadata datamanager instance, see the Debug Log for details.');
        }
    }

    /**
     * Determine the schema to use for a particular object
     *
     * @param array $schemadb The schema DB
     * @param midcom_core_dbaobject $object the object to work on
     * @return string The schema name
     */
    public static function find_schemaname(array $schemadb, midcom_core_dbaobject $object)
    {
        // Check if we have metadata schema defined in the schemadb specific for the object's schema or component
        $object_schema = $object->get_parameter('midcom.helper.datamanager2', 'schema_name');
        $component_schema = str_replace('.', '_', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT));
        if (   $object_schema == ''
            || !isset($schemadb[$object_schema]))
        {
            if (isset($schemadb[$component_schema]))
            {
                // No specific metadata schema for object, fall back to component-specific metadata schema
                $object_schema = $component_schema;
            }
            else
            {
                // No metadata schema for component, fall back to default
                $object_schema = 'metadata';
            }
        }
        return $object_schema;
    }

    function release_datamanager()
    {
        if (!is_null($this->_datamanager))
        {
            $this->_datamanager = null;
        }
    }

    /**
     * Frontend for setting a single metadata option
     *
     * @param string $key The key to set.
     * @param mixed $value The value to set.
     */
    public function set($key, $value)
    {
        if ($return = $this->_set_property($key, $value))
        {
            if ($this->__object->guid)
            {
                $return = $this->__object->update();
            }

            // Update the corresponding cache variable
            $this->on_update($key);
        }
        return $return;
    }

    public function __set($key, $value)
    {
        switch ($key)
        {
            case '_schemadb':
                $this->_schemadb = $value;
                return true;
            default:
                return $this->set($key, $value);
        }
    }

    /**
     * Frontend for setting multiple metadata options
     *
     * @param array $properties Array of key => value properties.
     */
    function set_multiple($properties)
    {
        foreach ($properties as $key => $value)
        {
            if (!$this->_set_property($key, $value))
            {
                return false;
            }
        }

        if (!$this->__object->guid)
        {
            return false;
        }

        if ($return = $this->__object->update())
        {
            // Update the corresponding cache variables
            array_map(array($this, 'on_update'), array_keys($properties));
        }
        return $return;
    }

    /**
     * Directly set a metadata option.
     *
     * The passed value will be stored using the follow transformations:
     *
     * - Storing into the approver field will automatically recognize Person Objects and simple
     *   IDs and transform them into a GUID.
     * - created can only be set with articles.
     * - creator, editor and edited cannot be set.
     *
     * Any error will trigger midcom_error.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to set.
     */
    private function _set_property($key, $value)
    {
        if (is_object($value))
        {
            $classname = get_class($value);
            debug_add("Can not set metadata '{$key}' property with '{$classname}' object as value", MIDCOM_LOG_WARN);

            return false;
        }

        // Store the RCS mode
        $rcs_mode = $this->__object->_use_rcs;

        switch ($key)
        {
            // Read-only properties
            case 'creator':
            case 'created':
            case 'revisor':
            case 'revised':
            case 'locker':
            case 'locked':
            case 'revision':
            case 'size':
            case 'deleted':
            case 'exported':
            case 'imported':
                midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                return false;

            // Writable properties
            case 'published':
            case 'schedulestart':
            case 'scheduleend':
                // Cast to ISO datetime
                if (!is_numeric($value))
                {
                    $value = 0;
                }
                if ($value == 0)
                {
                    $value = '0000-00-00 00:00:00';
                }
                else
                {
                    $value = gmstrftime('%Y-%m-%d %T', $value);
                }
                if (!extension_loaded('midgard'))
                {
                    if ($value == '0000-00-00 00:00:00')
                    {
                        $value = null;
                    }
                    else
                    {
                        $value = new midgard_datetime($value);
                    }
                }
                $this->__metadata->$key = $value;
                $value = true;
                break;

            case 'approver':
            case 'approved':
                // Prevent lock changes from creating new revisions
                $this->__object->_use_rcs = false;
                // Fall through
            case 'authors':
            case 'owner':
            case 'hidden':
            case 'navnoentry':
            case 'score':
                $this->__metadata->$key = $value;
                $value = true;
                break;

            // Fall-back for non-core properties
            default:
                $value = $this->__object->set_parameter('midcom.helper.metadata', $key, $value);
                break;
        }

        // Return the original RCS mode
        $this->__object->_use_rcs = $rcs_mode;

        return $value;
    }

    /**
     * This is the update event handler for the Metadata system. It must be called
     * whenever metadata changes to synchronize the various backwards-compatibility
     * values in place throughout the system.
     *
     * @param string $key The key that was updated. Leave empty for a complete update by the Datamanager.
     */
    function on_update($key = false)
    {
        if ($key)
        {
            unset ($this->_cache[$key]);
        }
        else
        {
            $this->_cache = Array();
        }

        if (!empty($this->guid))
        {
            midcom::get()->cache->invalidate($this->guid);
        }
    }

    /* ------- METADATA I/O INTERFACE -------- */

    /**
     * Retrieves a given metadata key, postprocesses it where necessary
     * and stores it into the local cache.
     *
     * - Person references (both guid and id) get resolved into the corresponding
     *   Person object.
     * - created, creator, edited and editor are taken from the corresponding
     *   MidgardObject fields.
     * - Parameters are accessed using $object->get_parameter directly
     *
     * Note, that we hide any errors from not existent properties explicitly,
     * as a few of the MidCOM objects do not support all of the predefined meta
     * data fields, PHP will default to "0" in these cases. For Person IDs, this
     * "0" is rewritten to "1" to use the MidgardAdministrator account instead.
     *
     * @param string $key The key to retrieve.
     */
    private function _retrieve_value($key)
    {
        switch ($key)
        {
            // Time-based properties
            case 'created':
            case 'revised':
            case 'published':
            case 'locked':
            case 'approved':
            case 'schedulestart':
            case 'scheduleend':
            case 'exported':
            case 'imported':
                if (   !extension_loaded('midgard')
                    && isset($this->__metadata->$key))
                {
                    //This is ugly, but seems the only possible way...
                    if ((string) $this->__metadata->$key === "0001-01-01T00:00:00+00:00")
                    {
                        $value = 0;
                    }
                    else
                    {
                        $value = (int) $this->__metadata->$key->format('U');
                    }
                }
                else if (   empty($this->__metadata->$key)
                         || $this->__metadata->$key == '0000-00-00 00:00:00')
                {
                    $value = 0;
                }
                else
                {
                    $value = strtotime("{$this->__metadata->$key} UTC");
                }
                break;

            // Person properties
            case 'creator':
            case 'revisor':
            case 'locker':
            case 'approver':
                $value = $this->__metadata->$key;
                if (!$value)
                {
                    // Fall back to "Midgard root user" if person is not found
                    static $root_user_guid = null;
                    if (!$root_user_guid)
                    {
                        $mc = new midgard_collector('midgard_person', 'id', 1);
                        $mc->set_key_property('guid');
                        $mc->execute();
                        $guids = $mc->list_keys();
                        if (empty($guids))
                        {
                            $root_user_guid = 'f6b665f1984503790ed91f39b11b5392';
                        }
                        else
                        {
                            $root_user_guid = key($guids);
                        }
                    }

                    $value = $root_user_guid;
                }
                break;

            // Other midgard_metadata properties
            case 'revision':
            case 'hidden':
            case 'navnoentry':
            case 'size':
            case 'deleted':
            case 'score':
            case 'authors':
            case 'owner':
                $value = $this->__metadata->$key;
                break;

            // Fall-back for non-core properties
            default:
                $dm = $this->get_datamanager();
                if (!isset($dm->types[$key]))
                {
                    // Fall back to the parameter reader for non-core MidCOM metadata params
                    $value = $this->__object->get_parameter('midcom.helper.metadata', $key);
                }
                else
                {
                    $value = $dm->types[$key]->convert_to_csv();
                }

                break;
        }
        $this->_cache[$key] = $value;
    }

    /* ------- CONVENIENCE METADATA INTERFACE --------- */

    /**
     * Checks whether the object has been approved since its last editing.
     *
     * @return boolean Indicating approval state.
     */
    public function is_approved()
    {
        return $this->__object->is_approved();
    }

    /**
     * Checks the object's visibility regarding scheduling and the hide flag.
     *
     * This does not check approval, use is_approved for that.
     *
     * @see midcom_helper_metadata::is_approved()
     * @return boolean Indicating visibility state.
     */
    public function is_visible()
    {
        if ($this->get('hidden'))
        {
            return false;
        }

        $now = time();
        if (   $this->get('schedulestart')
            && $this->get('schedulestart') > $now)
        {
            return false;
        }
        if (   $this->get('scheduleend')
            && $this->get('scheduleend') < $now)
        {
            return false;
        }
        return true;
    }

    /**
     * This is a helper function which indicates whether a given object may be shown onsite
     * taking approval, scheduling and visibility settings into account. The important point
     * here is that it also checks the global configuration defaults, so that this is
     * basically the same base on which NAP decides whether to show an item or not.
     *
     * @return boolean Indicating visibility.
     */
    function is_object_visible_onsite()
    {
        return
        (   (   midcom::get()->config->get('show_hidden_objects')
             || $this->is_visible())
         && (   midcom::get()->config->get('show_unapproved_objects')
             || $this->is_approved())
        );
    }

    /**
     * Approves the object.
     *
     * This sets the approved timestamp to the current time and the
     * approver person GUID to the GUID of the person currently
     * authenticated.
     */
    public function approve()
    {
        midcom::get()->auth->require_do('midcom:approve', $this->__object);
        midcom::get()->auth->require_do('midgard:update', $this->__object);

        if (!is_object($this->__object))
        {
            return false;
        }

        return $this->__object->approve();
    }

    /**
     * Approve, if object is already approved update
     * and approve.
     *
     * This is to get the approval timestamp to current time in all cases
     */
    function force_approve()
    {
        midcom::get()->auth->require_do('midcom:approve', $this->__object);
        midcom::get()->auth->require_do('midgard:update', $this->__object);
        if (!is_object($this->__object))
        {
            return false;
        }

        if ($this->__object->is_approved())
        {
            $this->__object->update();
        }
        return $this->__object->approve();
    }

    /**
     * Unapproves the object.
     *
     * This resets the approved timestamp and sets the
     * approver person GUID to the GUID of the person currently
     * authenticated.
     */
    public function unapprove()
    {
        midcom::get()->auth->require_do('midcom:approve', $this->__object);
        midcom::get()->auth->require_do('midgard:update', $this->__object);

        if (!is_object($this->__object))
        {
            return false;
        }

        return $this->__object->unapprove();
    }

    /* ------- CLASS MEMBER FUNCTIONS ------- */

    /**
     * Returns a metadata object for a given content object.
     *
     * You may bass any one of the following arguments to the function:
     *
     * - Any class derived from MidgardObject, you must only ensure, that the parameter
     *   and guid member functions stays available.
     * - Any valid GUID
     * - Any NAP object structure, the content object is deduced from MIDCOM_NAV_GUID in
     *   this case.
     *
     * @param mixed $source The object to attach to, this may be either a MidgardObject, a GUID or a NAP data structure (node or leaf).
     * @return midcom_helper_metadata The created metadata object.
     */
    public static function retrieve($source)
    {
        $object = null;

        if (is_object($source))
        {
            $object = $source;
            $guid = $source->guid;
        }
        else if (is_array($source))
        {
            if (   !array_key_exists(MIDCOM_NAV_GUID, $source)
                || is_null($source[MIDCOM_NAV_GUID]))
            {
                debug_print_r('We got an invalid input, cannot return metadata:', $source);
                return false;
            }
            $guid = $source[MIDCOM_NAV_GUID];
            $object = $source[MIDCOM_NAV_OBJECT];
        }
        else
        {
            $guid = $source;
        }

        if (   is_null($object)
            && mgd_is_guid($guid))
        {
            try
            {
                $object = midcom::get()->dbfactory->get_object_by_guid($guid);
            }
            catch (midcom_error $e)
            {
                debug_add("Failed to create a metadata instance for the GUID {$guid}: " . $e->getMessage(), MIDCOM_LOG_WARN);
                debug_print_r("Source was:", $source);

                return false;
            }
        }

        // $object is now populated, too
        return new self($guid, $object, midcom::get()->config->get('metadata_schema'));
    }

    /**
     * Check if the requested object is locked
     *
     * @return boolean          True if the object is locked, false if it isn't
     */
    public function is_locked()
    {
        // Object hasn't been marked to be edited
        if ($this->get('locked') == 0)
        {
            return false;
        }

        if (($this->get('locked') + (midcom::get()->config->get('metadata_lock_timeout') * 60)) < time())
        {
            // lock expired, explicitly clear lock
            $this->unlock();
            return false;
        }

        // Lock was created by the user, return "not locked"
        if (   !empty(midcom::get()->auth->user->guid)
            && $this->get('locker') === midcom::get()->auth->user->guid)
        {
            return false;
        }

        // Unlocked states checked and none matched, consider locked
        return $this->__object->is_locked();
    }

    /**
     * Set the object lock
     *
     * @param int $timeout   Length of the lock timeout
     * @return boolean       Indicating success
     */
    public function lock($timeout = null)
    {
        midcom::get()->auth->require_do('midgard:update', $this->__object);

        if (!$timeout)
        {
            $timeout = midcom::get()->config->get('metadata_lock_timeout');
        }

        if (   is_object($this->__object)
            && $this->__object->lock())
        {
            $this->_cache = array();
            return true;
        }

        return false;
    }

    /**
     * Check whether current user can unlock the object
     *
     * @return boolean indicating privileges
     * @todo enable specifying user ?
     */
    function can_unlock()
    {
        return (   $this->__object->can_do('midcom:unlock')
                || midcom::get()->auth->can_user_do('midcom:unlock', null, 'midcom_services_auth', 'midcom'));
    }

    /**
     * Unlock the object
     *
     * @return boolean    Indicating success
     */
    public function unlock()
    {
        if (   $this->can_unlock()
            && is_object($this->__object)
            && $this->__object->unlock())
        {
            $this->_cache = array();
            return true;
        }

        return false;
    }
}
