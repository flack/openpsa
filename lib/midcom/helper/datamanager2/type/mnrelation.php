<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 m:n membership management type.
 *
 * This subclass provides specialized I/O procedures which allow implicit management of
 * m:n object mappings. For example this can cover the person member assignments of a
 * midgard_group. The mapping class used is configurable, thus it should be adaptable to
 * any standard m:n relationship.
 *
 * The member objects used to construct this mapping must be fully
 * qualified DBA objects where the user owning the master object has full control, so
 * that the objects can be updated accordingly. It is recommended to make the member
 * objects children of the master objects. In addition, edit, delete and create rights
 * should always go together.
 *
 * To work properly, this class needs various information: First, there is the name of the
 * member class used to do the mapping. In addition to this, two fieldnames of that class
 * must be supplied, one for the GUID of the master object, the other for the identifier
 * of the membership.
 *
 * Optionally, you can set the class to use the master object ID in case of the GUID,
 * this is there for legacy code ("midgard_members") which do not use GUIDs for linking
 * yet. The linked object ("membership") is always referenced by the key selected in the
 * corresponding widget.
 *
 * An additional option allows you to limit the "visible" member key space: You specify
 * query constraints. When updating members, only member records matching
 * these constraints will be taken into account. This is quite useful in case you want to
 * split up a single selection into multiple "categories" for better usability. The
 * constraints are taken into account even when saving new keys so that all load and save
 * stays symmetrical. If you use this feature to separate multiple key namespaces from
 * each other, make sure that the various types do not overlap, otherwise one type will
 * overwrite the assignments of the other.
 *
 * When starting up, the type will only validate the existence of the mapping class. The
 * members specified will not be checked for performance reasons. In case something
 * wrong is specified there, it will surface during runtime, as invalid mapping entries
 * will be silently ignored (and thus saving won't work).
 *
 * This type should be set to a null storage location
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string mapping_class_name:</i> Mandatory option. Holds the name of the DBA
 *   class used for the mapping code. The class must satisfy the above rules.
 * - <i>string master_fieldname:</i> Mandatory option. Holds the fieldname containing
 *   the (GU)ID of the master object in the mapping table.
 * - <i>string member_fieldname:</i> Mandatory option. Holds the fieldname containing
 *   the membership keys in the mapping table.
 * - <i>boolean master_is_id:</i> Set this to true if you want the ID instead of the GUID
 *   to be used for mapping purposes. Defaults to false.
 * - <i>array constraints:</i> These constraints limit the
 *   number of valid member keys if set (see above). It defaults to null meaning no limit.
 * - <i>Array options:</i> The allowed option listing, a key/value map. Only the keys
 *   are stored in the storage location, using serialized storage. If you set this to
 *   null, <i>option_callback</i> has to be defined instead. You may not define both
 *   options.
 * - <i>string option_callback:</i> This must be the name of an available class which
 *   handles the actual option listing. See below how such a class has to look like.
 *   If you set this to null, <i>options</i> has to be defined instead. You may not
 *   define both options.
 * - <i>mixed option_callback_arg:</i> An additional argument passed to the constructor
 *   of the option callback, defaulting to null.
 * - <i>boolean csv_export_key:</i> If this flag is set, the CSV export will store the
 *   field key instead of its value. This is only useful if the foreign tables referenced
 *   are available at the site of import. This flag is not set by default. Note, that
 *   this does not affect import, which is only available with keys, not values.
 * - <i>boolean sortable:</i> Switch for determining if the order selected by the widget
 *   should be stored to the metadata object
 * - <i>string sortable_sort_order:</i> Direction that metadata.score should go. If this
 *   is set to `ASC`, lower scores will be displayed first. If this is set to `DESC`, higher
 *   scores will be displayed first. `DESC` is default, since then new member objects will
 *   be left at the end of the line rather than appearing first. This field is not case
 *   sensitive and string can be extended e.g. to `ascend`.
 * - <i>array additional_fields:</i> Additional fields that should be set on the mnrelation object
 *
 * (These list is complete, including all allowed options from the base type. Base type
 * options not listed here may not be used.)
 *
 * <b>Option Callback class</b>
 *
 * See base type.
 *
 * <b>Implementation notes</b>
 *
 * This class essentially extends the select type, rewriting its I/O code to suite the
 * needs of a member management type.
 *
 * Therefore, we force-override a few settings to ensure operability: allow_other
 * will always be false, while allow_multiple always be true.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_mnrelation extends midcom_helper_datamanager2_type_select
{
    /**
     * Mandatory option. Holds the name of the DBA class used for the mapping code
     *
     * @var string
     */
    public $mapping_class_name;

    /**
     * Mandatory option. Holds the fieldname containing
     * the (GU)ID of the master object in the mapping table.
     *
     * @var string
     */
    public $master_fieldname;

    /**
     * Mandatory option. Holds the fieldname containing
     * the membership keys in the mapping table.
     *
     * @var string
     */
    public $member_fieldname;

    /**
     * Set this to true if you want the ID instead of the GUID
     * to be used for mapping purposes.
     *
     * @var boolean
     */
    public $master_is_id = false;

    /**
     * Array of constraints, always AND
     *
     * Example:
     * <code>
     *     'constraints' => array
     *     (
     *         array
     *         (
     *             'field' => 'username',
     *             'op' => '<>',
     *             'value' => '',
     *         ),
     *     ),
     * </code>
     *
     * @var array
     */
    public $constraints = array();

    /**
     * Set this to false to use with chooser, this skips making sure the key exists in option list
     * Mainly used to avoid unnecessary seeks to load all a ton of objects to the options list. This is false
     * by default for mn relations, since by its nature this is intended for dynamic searches.
     *
     * @var boolean
     */
    public $require_corresponding_option = false;

    /**
     * This is a QB resultset of all membership objects currently constructed. It is indexed
     * by membership record guid. It will be populated during startup, when the stored data is
     * loaded. During save, this list will be used to determine the objects that have to be
     * deleted.
     *
     * Only objects matching the constraints will be memorized.
     *
     * @var Array
     */
    private $_membership_objects = null;

    /**
     * Should the sorting feature be enabled. This will affect the way chooser widget will act
     * and how the results will be presented. If the sorting feature is enabled,
     *
     * @var boolean
     */
    public $sortable = false;

    /**
     * Sort order. Which direction should metadata.score force the results. This should be either
     * `ASC` or `DESC`
     *
     * @var string
     */
    public $sortable_sort_order = 'DESC';

    /**
     * Sorted order, which is returned by the widget.
     *
     * @var Array
     */
    public $sorted_order = array();

    /**
     * Additional fields to set on the object
     *
     * @var Array
     */
    public $additional_fields = array();

    /**
     * This flag controls whether multiple selections are allowed, or not.
     *
     * @var boolean
     */
    public $allow_multiple = true;

    /**
     * Initialize the class, if necessary, create a callback instance, otherwise
     * validate that an option array is present.
     */
    public function _on_initialize()
    {
        if (   !$this->mapping_class_name
            || !$this->master_fieldname
            || !$this->member_fieldname) {
            throw new midcom_error
            (
                'The configuration options mapping_class_name, master_filename and member_fieldname
                 must be defined for  any mnselect type.'
            );
        }

        if (!class_exists($this->mapping_class_name)) {
            throw new midcom_error("The mapping class {$this->mapping_class_name} does not exist.");
        }

        $this->allow_other = false;
        parent::_on_initialize();
    }

    /**
     * Returns the foreign key of the master object. This is either the ID or the GUID of
     * the master object, depending on the $master_is_id member.
     *
     * @var string Foreign key for the master field in the mapping table.
     */
    private function _get_master_foreign_key()
    {
        if ($this->master_is_id) {
            return $this->storage->object->id;
        }
        return $this->storage->object->guid;
    }

    /**
     * Loads all membership records from the database. May only be called if a storage object is
     * defined.
     */
    private function _load_membership_objects()
    {
        $qb = midcom::get()->dbfactory->new_query_builder($this->mapping_class_name);
        $qb->add_constraint($this->master_fieldname, '=', $this->_get_master_foreign_key());

        if (   $this->sortable
            && preg_match('/^(ASC|DESC)/i', $this->sortable_sort_order, $regs)) {
            $order = strtoupper($regs[1]);
            $qb->add_order('metadata.score', $order);
        }

        foreach ($this->constraints as $constraint) {
            $qb->add_constraint($this->member_fieldname . '.' . $constraint['field'], $constraint['op'], $constraint['value']);
        }

        if (!empty($this->additional_fields)) {
            foreach ($this->additional_fields as $fieldname => $value) {
                $qb->add_constraint($fieldname, '=', $value);
            }
        }

        $this->_membership_objects = $qb->execute();
    }

    /**
     * Reads all entries from the mapping table. This overrides the base types I/O code completely.
     *
     * @var mixed $source
     */
    public function convert_from_storage($source)
    {
        $this->selection = array();
        // Check for the defaults section first
        if (is_array($source)) {
            foreach ($source as $id) {
                if (is_object($id)) {
                    $this->selection[] = ($this->master_is_id) ? $id->id : $id->guid;
                } else {
                    $this->selection[] = $id;
                }
            }
        }

        if (!$this->storage->object) {
            // That's all folks, no storage object, thus we cannot continue.
            return;
        }

        $this->_load_membership_objects();

        foreach ($this->_membership_objects as $member) {
            $key = $member->{$this->member_fieldname};
            if (   !$this->require_corresponding_option
                || $this->key_exists($key)) {
                $this->selection[] = $key;
            } else {
                debug_add("Encountered unknown key {$key} for field {$this->name}, skipping it.", MIDCOM_LOG_INFO);
            }
        }
    }

    /**
     * Updates the mapping table to match the current selection.
     *
     * @return Returns null.
     */
    public function convert_to_storage()
    {
        if (!$this->storage->object) {
            // That's all folks, no storage object, thus we cannot continue.
            debug_add("Tried to save the membership info for field {$this->name}, but no storage object was set. Ignoring silently.",
                MIDCOM_LOG_WARN);
            return;
        }

        // Build a reverse lookup map for the existing membership objects.
        // We map keys to _membership_objects indexes.
        // If we have duplicate keys, the latter will overwrite the former, leaving the dupe for deletion.
        $existing_members = array();
        foreach ($this->_membership_objects as $index => $member) {
            $key = $member->{$this->member_fieldname};
            $existing_members[$key] = $index;
        }

        //This creates new memberships and moves existing ones out of $this->_membership_objects
        $new_membership_objects = $this->_get_new_membership_objects($existing_members);

        // Delete all remaining objects, then update the membership_objects list
        foreach ($this->_membership_objects as $member) {
            if (!$member->delete()) {
                debug_add("Failed to delete a no longer needed member record #{$member->id}, ignoring silently. " .
                    'Last Midgard error was: ' .
                    midcom_connection::get_error_string(),
                    MIDCOM_LOG_ERROR);
                debug_print_r('Tried to delete this object:', $member);
            }
        }

        $this->_membership_objects = $new_membership_objects;

        return $this->selection;
    }

    private function _get_new_membership_objects($existing_members)
    {
        $new_membership_objects = array();
        // Cache the total quantity of items and get the order if the field is supposed to store the member order
        if (   $this->sortable
            && isset($this->sorted_order)) {
            $count = count($this->sorted_order);

            if (preg_match('/ASC/i', $this->sortable_sort_order)) {
                $direction = 'asc';
            } else {
                $direction = 'desc';
            }
        }

        $i = 0;

        foreach ($this->selection as $key) {
            //TODO: Ideally, selections not matching the constraints should be filtered out, but
            //for now, we trust the component author to correctly configure their stuff

            // Do we have this key already? If yes, move it to the new list, otherwise create it.
            if (array_key_exists($key, $existing_members)) {
                // Update the existing member
                if ($this->sortable) {
                    $index = $existing_members[$key];

                    if ($direction === 'asc') {
                        $this->_membership_objects[$index]->metadata->score = $i;
                    } else {
                        $this->_membership_objects[$index]->metadata->score = $count - $i;
                    }

                    if (!$this->_membership_objects[$index]->update()) {
                        debug_add("Failed to update the member record for key {$key}. Couldn't store the order information", MIDCOM_LOG_ERROR);
                        debug_add('Last Midgard error was ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                        debug_print_r('Tried to update this object', $this->_membership_objects[$index]);
                    }

                    $i++;
                }

                $index = $existing_members[$key];
                $new_membership_objects[] = $this->_membership_objects[$index];
                unset ($this->_membership_objects[$index]);
            } else {
                // Create new member
                $member = new $this->mapping_class_name();
                $member->{$this->master_fieldname} = $this->_get_master_foreign_key();
                $member->{$this->member_fieldname} = $key;

                // Set the score if requested
                if ($this->sortable) {
                    if ($direction === 'asc') {
                        $member->metadata->score = $i;
                    } else {
                        $member->metadata->score = $count - $i;
                    }

                    $i++;
                }

                if (!empty($this->additional_fields)) {
                    foreach ($this->additional_fields as $fieldname => $value) {
                        // Determine what to do if using dot (.) in the additional fields,
                        if (preg_match('/^(.+)\.(.+)$/', $fieldname, $regs)) {
                            $domain = $regs[1];
                            $key = $regs[2];

                            // Determine what should be done with conjunction
                            switch ($domain) {
                                case 'metadata':
                                    $member->metadata->$key = $value;
                                    break;

                                case 'parameter':
                                    $member->parameter('midcom.helper.datamanager2.mnrelation', $key, $value);
                                    break;
                            }

                            continue;
                        }

                        $member->{$fieldname} = $value;
                    }
                }

                if (!$member->create()) {
                    debug_add("Failed to create a new member record for key {$key}, skipping it. " .
                        'Last Midgard error was: ' .
                        midcom_connection::get_error_string(),
                        MIDCOM_LOG_ERROR);
                    debug_print_r('Tried to create this object:', $member);
                    continue;
                }
                $new_membership_objects[] = $member;
            }
        }
        return $new_membership_objects;
    }

    public function convert_to_csv()
    {
        $values = $this->combine_values();
        return implode($values, ', ');
    }

    public function convert_to_raw()
    {
        return null;
    }
}
