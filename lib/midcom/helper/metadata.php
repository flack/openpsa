<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;

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
 * $meta = $node[MIDCOM_NAV_OBJECT]->metadata;
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
 * $article->metadata->approve();
 * </code>
 *
 * @property integer $schedulestart The time upon which the object should be made visible. 0 for no restriction.
 * @property integer $scheduleend The time upon which the object should be made invisible. 0 for no restriction.
 * @property boolean $navnoentry Set this to true if you do not want this object to appear in the navigation without it being completely hidden.
 * @property boolean $hidden Set this to true to hide the object on-site, overriding scheduling.
 * @property integer $published The publication time of the object.
 * @property string $publisher The person that published the object (i.e. author), read-only except on articles and pages.
 * @property string $authors The persons that worked on the object, pipe-separated list of GUIDs
 * @property string $owner The group that owns the object.
 * @property-read integer $created The creation time of the object.
 * @property-read string $creator The person that created the object.
 * @property-read integer $revised The last-modified time of the object.
 * @property-read string $revisor The person that modified the object.
 * @property-read integer $revision The object's revision.
 * @property-read integer $locked The lock time of the object.
 * @property-read string $locker The person that locked the object.
 * @property-read integer $size The object's size in bytes.
 * @property-read boolean $deleted Is the object deleted.
 * @property integer $approved The time of approval of the object, or 0 if not approved. Set automatically through approve/unapprove.
 * @property string $approver The person that approved/unapproved the object. Set automatically through approve/unapprove.
 * @property integer $score The object's score for sorting.
 * @property midcom_core_dbaobject $object Object to which we are attached.
 * @package midcom.helper
 */
class midcom_helper_metadata
{
    /**
     * @var midcom_core_dbaobject
     */
    private $__object;

    /**
     * Metadata object of the current object
     *
     * @var midgard\portable\api\metadata
     */
    private $__metadata;

    /**
     * Holds the values already read from the database.
     *
     * @var Array
     */
    private $_cache = [];

    /**
     * Datamanager instance for the given object.
     *
     * @var datamanager
     */
    private $_datamanager;

    private $field_config = [
        'readonly' => ['creator', 'created', 'revisor', 'revised', 'locker', 'locked', 'revision', 'size', 'deleted', 'exported', 'imported'],
        'timebased' => ['created', 'revised', 'published', 'locked', 'approved', 'schedulestart', 'scheduleend', 'exported', 'imported'],
        'person' => ['creator', 'revisor', 'locker', 'approver'],
        'other' => ['authors', 'owner', 'hidden', 'navnoentry', 'score', 'revision', 'size', 'deleted']
    ];

    /**
     * This will construct a new metadata object for an existing content object.
     *
     * @param midcom_core_dbaobject $object The object to attach to.
     */
    public function __construct(midcom_core_dbaobject $object)
    {
        $this->__metadata = $object->__object->metadata;
        $this->__object = $object;
    }

    /* ------- BASIC METADATA INTERFACE --------- */

    /**
     * Return a single metadata key from the object. The return
     * type depends on the metadata key that is requested (see the class introduction).
     *
     * You will not get the data from the datamanager using this calls, but the only
     * slightly post-processed metadata values. See _retrieve_value for post processing.
     *
     * @see midcom_helper_metadata::_retrieve_value()
     * @param string $key The key to retrieve
     * @return mixed The key's value.
     */
    public function get($key)
    {
        if (!isset($this->_cache[$key])) {
            $this->_cache[$key] = $this->_retrieve_value($key);
        }

        return $this->_cache[$key];
    }

    public function __get($key)
    {
        if ($key == 'object') {
            return $this->__object;
        }
        return $this->get($key);
    }

    public function __isset($key)
    {
        if (!isset($this->_cache[$key])) {
            $this->_cache[$key] = $this->_retrieve_value($key);
        }

        return isset($this->_cache[$key]);
    }

    /**
     * Return a Datamanager instance for the current object.
     *
     * Also, whenever the containing datamanager stores its data, you
     * <b>must</b> call the on_update() method of this class. This is
     * very important or backwards compatibility will be broken.
     *
     * @return datamanager A initialized Datamanager instance for the selected object.
     * @see midcom_helper_metadata::on_update()
     */
    public function get_datamanager()
    {
        if (is_null($this->_datamanager)) {
            $this->load_datamanager();
        }

        return $this->_datamanager;
    }

    /**
     * Loads the datamanager for this instance. This will patch the schema in case we
     * are dealing with an article.
     */
    private function load_datamanager()
    {
        $schemadb = schemadb::from_path(midcom::get()->config->get('metadata_schema'));

        // Check if we have metadata schema defined in the schemadb specific for the object's schema or component
        $object_schema = $this->__object->get_parameter('midcom.helper.datamanager2', 'schema_name');
        if ($object_schema == '' || !$schemadb->has($object_schema)) {
            $component_schema = str_replace('.', '_', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT));
            if ($schemadb->has($component_schema)) {
                // No specific metadata schema for object, fall back to component-specific metadata schema
                $object_schema = $component_schema;
            } else {
                // No metadata schema for component, fall back to default
                $object_schema = 'metadata';
            }
        }
        $this->_datamanager = new datamanager($schemadb);
        $this->_datamanager->set_storage($this->__object, $object_schema);
    }

    public function release_datamanager()
    {
        if (!is_null($this->_datamanager)) {
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
        // Store the RCS mode
        $rcs_mode = $this->__object->_use_rcs;

        if ($return = $this->_set_property($key, $value)) {
            if ($this->__object->guid) {
                $return = $this->__object->update();
            }

            // Update the corresponding cache variable
            $this->on_update($key);
        }
        // Return the original RCS mode
        $this->__object->_use_rcs = $rcs_mode;
        return $return;
    }

    public function __set($key, $value)
    {
        return $this->set($key, $value);
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
        if (is_object($value)) {
            $classname = get_class($value);
            debug_add("Can not set metadata '{$key}' property with '{$classname}' object as value", MIDCOM_LOG_WARN);
            return false;
        }

        if (in_array($key, $this->field_config['readonly'])) {
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }

        if (in_array($key, ['approver', 'approved'])) {
            // Prevent lock changes from creating new revisions
            $this->__object->_use_rcs = false;
        }

        if (in_array($key, $this->field_config['timebased'])) {
            if (!is_numeric($value) || $value == 0) {
                $value = null;
            } else {
                $value = new midgard_datetime(gmstrftime('%Y-%m-%d %T', $value));
            }
        } elseif (!in_array($key, $this->field_config['other']) && $key !== 'approver') {
            // Fall-back for non-core properties
            return $this->__object->set_parameter('midcom.helper.metadata', $key, $value);
        }

        $this->__metadata->$key = $value;
        return true;
    }

    /**
     * This is the update event handler for the Metadata system. It must be called
     * whenever metadata changes to synchronize the various backwards-compatibility
     * values in place throughout the system.
     *
     * @param string $key The key that was updated. Leave empty for a complete update by the Datamanager.
     */
    private function on_update($key = null)
    {
        if ($key) {
            unset($this->_cache[$key]);
        } else {
            $this->_cache = [];
        }

        if (!empty($this->__object->guid)) {
            midcom::get()->cache->invalidate($this->__object->guid);
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
        if (in_array($key, $this->field_config['timebased'])) {
            // This is ugly, but seems the only possible way...
            if (   isset($this->__metadata->$key)
                && (string) $this->__metadata->$key !== "0001-01-01T00:00:00+00:00") {
                return (int) $this->__metadata->$key->format('U');
            }
            return 0;
        }
        if (in_array($key, $this->field_config['person'])) {
            if (!$this->__metadata->$key) {
                // Fall back to "Midgard root user" if person is not found
                static $root_user_guid = null;
                if (!$root_user_guid) {
                    $mc = new midgard_collector('midgard_person', 'id', 1);
                    $mc->set_key_property('guid');
                    $mc->execute();
                    $guids = $mc->list_keys();
                    if (empty($guids)) {
                        $root_user_guid = 'f6b665f1984503790ed91f39b11b5392';
                    } else {
                        $root_user_guid = key($guids);
                    }
                }

                return $root_user_guid;
            }
            return $this->__metadata->$key;
        }
        if (!in_array($key, $this->field_config['other'])) {
            // Fall-back for non-core properties
            $dm = $this->get_datamanager();
            if (!$dm->get_schema()->has_field($key)) {
                // Fall back to the parameter reader for non-core MidCOM metadata params
                return $this->__object->get_parameter('midcom.helper.metadata', $key);
            }
            return $dm->get_content_csv()[$key];
        }
        return $this->__metadata->$key;
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
        if ($this->get('hidden')) {
            return false;
        }

        $now = time();
        if (   $this->get('schedulestart')
            && $this->get('schedulestart') > $now) {
            return false;
        }
        if (   $this->get('scheduleend')
            && $this->get('scheduleend') < $now) {
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
    public function is_object_visible_onsite()
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

        if (!is_object($this->__object)) {
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
        if (!is_object($this->__object)) {
            return false;
        }

        if ($this->__object->is_approved()) {
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

        if (!is_object($this->__object)) {
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
     *
     * @param mixed $source The object to attach to, this may be either a MidgardObject or a GUID.
     * @return midcom_helper_metadata The created metadata object.
     */
    public static function retrieve($source)
    {
        $object = null;

        if (is_object($source)) {
            $object = $source;
            $guid = $source->guid;
        } else {
            $guid = $source;
        }

        if (   is_null($object)
            && mgd_is_guid($guid)) {
            try {
                $object = midcom::get()->dbfactory->get_object_by_guid($guid);
            } catch (midcom_error $e) {
                debug_add("Failed to create a metadata instance for the GUID {$guid}: " . $e->getMessage(), MIDCOM_LOG_WARN);
                debug_print_r("Source was:", $source);

                return false;
            }
        }

        // $object is now populated, too
        return new self($object);
    }

    /**
     * Check if the requested object is locked
     *
     * @return boolean          True if the object is locked, false if it isn't
     */
    public function is_locked()
    {
        // Object hasn't been marked to be edited
        if ($this->get('locked') == 0) {
            return false;
        }

        if (($this->get('locked') + (midcom::get()->config->get('metadata_lock_timeout') * 60)) < time()) {
            // lock expired, explicitly clear lock
            $this->unlock();
            return false;
        }

        // Lock was created by the user, return "not locked"
        if (   !empty(midcom::get()->auth->user->guid)
            && $this->get('locker') === midcom::get()->auth->user->guid) {
            return false;
        }

        // Unlocked states checked and none matched, consider locked
        return $this->__object->is_locked();
    }

    /**
     * Set the object lock
     *
     * @return boolean       Indicating success
     */
    public function lock()
    {
        midcom::get()->auth->require_do('midgard:update', $this->__object);

        if (   is_object($this->__object)
            && $this->__object->lock()) {
            $this->_cache = [];
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
    public function can_unlock()
    {
        return (   $this->__object->can_do('midcom:unlock')
                || midcom::get()->auth->can_user_do('midcom:unlock', null, midcom_services_auth::class));
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
            && $this->__object->unlock()) {
            $this->_cache = [];
            return true;
        }

        return false;
    }
}
