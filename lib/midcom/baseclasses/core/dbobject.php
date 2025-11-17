<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\events\dbaevent;

/**
 * This class only contains static functions which are there to hook into
 * the classes you derive from the midcom_core_dbaobject.
 *
 * The static members will invoke a number of callback methods so that you should
 * normally never have to override the base midgard methods like update or the like.
 *
 * @package midcom.baseclasses
 */
class midcom_baseclasses_core_dbobject
{
    /**
     * "Pre-flight" checks for update method
     *
     * Separated so that dbfactory->import() can reuse the code
     */
    public static function update_pre_checks(midcom_core_dbaobject $object) : bool
    {
        if (!$object->can_do('midgard:update')) {
            debug_add("Failed to update object, update privilege on the " . $object::class . " {$object->id} not granted for the current user.",
                MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }
        if (!$object->_on_updating()) {
            debug_add("The _on_updating event handler returned false.");
            return false;
        }
        // Still check name uniqueness
        return self::_pre_check_name($object);
    }

    /**
     * Execute a DB update of the object passed. This will call the corresponding
     * event handlers. Calling sequence with method signatures:
     *
     * 1. Validate privileges using can_do. The user needs midgard:update privilege on the content object.
     * 2. bool $object->_on_updating() is executed. If it returns false, update is aborted.
     * 3. bool $object->__object->update() is executed to do the actual DB update. This has to execute parent::update()
     *    and return its value, nothing else.
     * 4. void $object->_on_updated() is executed to notify the class from a successful DB update.
     */
    public static function update(midcom_core_dbaobject $object) : bool
    {
        if (!self::update_pre_checks($object)) {
            debug_add('Pre-flight check returned false', MIDCOM_LOG_ERROR);
            return false;
        }

        if (!$object->__object->update()) {
            debug_add("Failed to update the record, last Midgard error: " . midcom_connection::get_error_string());
            return false;
        }

        self::update_post_ops($object);

        return true;
    }

    /**
     * Post object creation operations for create
     *
     * Separated so that dbfactory->import() can reuse the code
     */
    public static function update_post_ops(midcom_core_dbaobject $object)
    {
        $object->_on_updated();

        midcom::get()->dispatcher->dispatch(new dbaevent($object), dbaevent::UPDATE);
    }

    /**
     * Add full privileges to the owner of the object.
     * This is essentially sets the midgard:owner privilege for the current user.
     */
    private static function _set_owner_privileges(midcom_core_dbaobject $object)
    {
        if (!midcom::get()->auth->user) {
            debug_add("Could not retrieve the midcom_core_user instance for the creator of " . $object::class . " {$object->guid}, skipping owner privilege assignment.",
                MIDCOM_LOG_INFO);
            return;
        }

        // Circumvent the main privilege class as we need full access here regardless of
        // the actual circumstances.
        $privilege = new midcom_core_privilege_db();
        $privilege->assignee = midcom::get()->auth->user->id;
        $privilege->privilegename = 'midgard:owner';
        $privilege->objectguid = $object->guid;
        $privilege->value = MIDCOM_PRIVILEGE_ALLOW;

        if (!$privilege->create()) {
            debug_add("Could not set the owner privilege {$privilege->privilegename} for {$object->guid}, see debug level log for details. Last Midgard Error: " . midcom_connection::get_error_string(),
                MIDCOM_LOG_WARN);
        }
    }

    /**
     * "Pre-flight" checks for create method
     *
     * Separated so that dbfactory->import() can reuse the code
     */
    public static function create_pre_checks(midcom_core_dbaobject $object) : bool
    {
        if ($parent = $object->get_parent()) {
            // Attachments are a special case
            if ($object instanceof midcom_db_attachment) {
                if (   !$parent->can_do('midgard:attachments')
                    || !$parent->can_do('midgard:update')) {
                    debug_add("Failed to create attachment, update or attachments privilege on the parent " . $parent::class . " {$parent->guid} not granted for the current user.",
                        MIDCOM_LOG_ERROR);
                    midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                    return false;
                }
            } elseif (   !$parent->can_do('midgard:create')
                      && !midcom::get()->auth->can_user_do('midgard:create', class: $object::class)) {
                debug_add("Failed to create object, create privilege on the parent " . $parent::class . " {$parent->guid} or the actual object class not granted for the current user.",
                    MIDCOM_LOG_ERROR);
                midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
                return false;
            }
        } elseif (!midcom::get()->auth->can_user_do('midgard:create', class: $object::class)) {
            debug_add("Failed to create object, general create privilege not granted for the current user.", MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }

        if (!$object->_on_creating()) {
            debug_add("The _on_creating event handler returned false.");
            return false;
        }

        // Still check name uniqueness
        return self::_pre_check_name($object);
    }

    /**
     * Helper method to call in the _xxx_pre_checks, handles the API
     * level checks and automatic operations as specified in ticket #809
     *
     * @see http://trac.midgard-project.org/ticket/809
     * Quoting the ticket API-level section:
     * <pre>
     *      1. Checks will be done in the pre-flight check phase (ie just after _on_creating/_on_updating)
     *      2. If name is not unique false is returned for pre-flight check, preventing create/update
     *          2.2 UNLESS a property in the object ('allow_name_catenate') is set to true in which case unique one is generated by catenating an incrementing number to the name.
     *      3. if name is empty unique name is generated from title property (unless title is empty too)
     *      4. if name is not URL-safe false is returned
     * </pre>
     *
     * @see midcom_helper_reflector_nameresolver::name_is_safe()
     * @see midcom_helper_reflector_nameresolver::name_is_unique()
     * @see midcom_helper_reflector_nameresolver::generate_unique_name()
     */
    private static function _pre_check_name(midcom_core_dbaobject $object) : bool
    {
        // Make sure name is empty of unique if the object has such property
        $name_property = midcom_helper_reflector::get_name_property($object);
        if (empty($name_property)) {
            // This object has no name property, return early
            return true;
        }

        $resolver = new midcom_helper_reflector_nameresolver($object);

        /**
         * If name is empty, try to generate new, unique one
         *
         * @see http://trac.midgard-project.org/ticket/809
         */
        if (empty($object->{$name_property})) {
            // name is empty, try to generate
            $object->{$name_property} = (string) $resolver->generate_unique_name();
            if (empty($object->{$name_property})) {
                return true;
            }
        }

        // Enforce URL-safe names
        if (!$resolver->name_is_safe()) {
            midcom_connection::set_error(MGD_ERR_INVALID_NAME);
            return false;
        }

        // Enforce unique (or empty) names
        if (!$resolver->name_is_unique()) {
            if ($object->allow_name_catenate) {
                // Transparent catenation allowed, let's try again.
                if ($new_name = $resolver->generate_unique_name()) {
                    $object->{$name_property} = $new_name;
                    return true;
                }
                debug_add('allow_name_catenate was set but midcom_helper_reflector_nameresolver::generate_unique_name() returned empty value, falling through', MIDCOM_LOG_WARN);
            }
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }

        // All checks ok, we're fine.
        return true;
    }

    /**
     * Execute a DB create of the object passed. This will call the corresponding
     * event handlers. Calling sequence with method signatures:
     *
     * 1. Validate privileges using can_do. The user needs midgard:create privilege to the parent object or in general, if there is no parent.
     * 2. bool $object->_on_creating() is executed. If it returns false, create is aborted.
     * 3. bool $object->__object->create() is executed to do the actual DB create. This has to execute parent::create()
     *    and return its value, nothing else.
     * 4. void $object->_on_created() is executed to notify the class from a successful DB creation.
     */
    public static function create(midcom_core_dbaobject $object) : bool
    {
        if (!self::create_pre_checks($object)) {
            debug_add('Pre-flight check returned false', MIDCOM_LOG_ERROR);
            return false;
        }

        if (midcom::get()->auth->user !== null) {
            // Default the authors to current user
            if (empty($object->metadata->authors)) {
                $object->metadata->set('authors', "|" . midcom::get()->auth->user->guid . "|");
            }

            // Default the owner to first group of current user
            if (   empty($object->metadata->owner)
                && $first_group = midcom::get()->auth->user->get_first_group_guid()) {
                $object->metadata->set('owner', $first_group);
            }
        }
        // Default the publication time to current date/time
        if (empty($object->metadata->published)) {
            $object->metadata->set('published', time());
        }

        if (!$object->__object->create()) {
            debug_add("Failed to create " . $object::class . ", last Midgard error: " . midcom_connection::get_error_string());
            return false;
        }

        self::create_post_ops($object);

        return true;
    }

    /**
     * Post object creation operations for create
     *
     * Separated so that dbfactory->import() can reuse the code
     */
    public static function create_post_ops(midcom_core_dbaobject $object)
    {
        self::_set_owner_privileges($object);

        $object->_on_created();
        midcom::get()->dispatcher->dispatch(new dbaevent($object), dbaevent::CREATE);
    }

    /**
     * Execute a DB delete of the object passed. This will call the corresponding
     * event handlers. Calling sequence with method signatures:
     *
     * 1. Validate privileges using can_do. The user needs midgard:delete privilege on the content object.
     * 2. bool $object->_on_deleting() is executed. If it returns false, delete is aborted.
     * 3. All extensions of the object are deleted
     * 4. bool $object->__object->delete() is executed to do the actual DB delete. This has to execute parent::delete()
     *    and return its value, nothing else.
     * 5. void $object->_on_deleted() is executed to notify the class from a successful DB deletion.
     */
    public static function delete(midcom_core_dbaobject $object) : bool
    {
        if (!self::delete_pre_checks($object)) {
            debug_add('Pre-flight check returned false', MIDCOM_LOG_ERROR);
            return false;
        }

        // Delete all extensions:
        // Attachments can't have attachments so no need to query those
        if (!$object instanceof midcom_db_attachment) {
            foreach ($object->list_attachments() as $attachment) {
                if (!$attachment->delete()) {
                    debug_add("Failed to delete attachment ID {$attachment->id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    return false;
                }
            }
        }

        $query = new midgard_query_builder('midgard_parameter');
        $query->add_constraint('parentguid', '=', $object->guid);
        foreach ($query->execute() as $parameter) {
            if (!$parameter->delete()) {
                debug_add("Failed to delete parameter ID {$parameter->id}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                return false;
            }
        }

        if (!self::_delete_privileges($object)) {
            debug_add('Failed to delete the object privileges.', MIDCOM_LOG_INFO);
            return false;
        }

        // Finally, delete the object itself
        if (!$object->__object->delete()) {
            debug_add("Failed to delete " . $object::class . ", last Midgard error: " . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return false;
        }

        // Explicitly set this in case someone needs to check against it
        self::delete_post_ops($object);

        return true;
    }

    /**
     * Unconditionally drop all privileges assigned to the given object.
     * Called upon successful delete
     */
    private static function _delete_privileges(midcom_core_dbaobject $object) : bool
    {
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('objectguid', '=', $object->guid);

        foreach ($qb->execute() as $dbpriv) {
            if (!$dbpriv->purge()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Execute a DB delete of the object passed and delete its descendants. This will call the corresponding
     * event handlers. Calling sequence with method signatures:
     *
     * 1. Get all of the child objects
     * 2. Delete them recursively starting from the top, working towards the root
     * 3. Finally delete the root object
     */
    public static function delete_tree(midcom_core_dbaobject $object) : bool
    {
        foreach (midcom_helper_reflector_tree::get_child_objects($object) as $children) {
            // Delete first the descendants
            foreach ($children as $child) {
                //Inherit RCS status (so that f.x. large tree deletions can run faster)
                $child->_use_rcs = $object->_use_rcs;
                if (!self::delete_tree($child)) {
                    debug_print_r('Failed to delete the children of this object:', $object, MIDCOM_LOG_INFO);
                    return false;
                }
            }
        }
        if (!self::delete($object)) {
            debug_print_r('Failed to delete the object', $object, MIDCOM_LOG_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Post object creation operations for delete
     *
     * Separated so that dbfactory->import() can reuse the code
     */
    public static function delete_post_ops(midcom_core_dbaobject $object)
    {
        $object->_on_deleted();
        midcom::get()->dispatcher->dispatch(new dbaevent($object), dbaevent::DELETE);
    }

    /**
     * After we instantiated the midgard object do some post processing and ACL checks
     *
     * @see load()
     */
    public static function post_db_load_checks(midcom_core_dbaobject $object)
    {
        if (!$object->can_do('midgard:read')) {
            debug_add("Failed to load object, read privilege on the " . $object::class . " {$object->guid} not granted for the current user.");
            throw new midcom_error_forbidden();
        }
        $object->_on_loaded();

        // Register the GUID as loaded in this request
        midcom::get()->cache->content->register($object->guid);
    }

    /**
     * This is a simple wrapper with (currently) no additional functionality
     * over get_by_id that resynchronizes the object state with the database.
     * Use this if you think that your current object is stale. It does full
     * access control.
     *
     * On any failure, the object is cleared.
     */
    public static function refresh(midcom_core_dbaobject $object) : bool
    {
        /**
         * Use try/catch here since the object might have been deleted...
         * @see http://trac.midgard-project.org/ticket/927
         */
        try {
            return $object->get_by_id($object->id);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * This call wraps the original get_by_id call to provide access control.
     * The calling sequence is as with the corresponding constructor.
     */
    public static function get_by_id(midcom_core_dbaobject $object, int $id) : bool
    {
        if (!$id) {
            debug_add("Failed to load " . $object::class . " object, incorrect ID provided.", MIDCOM_LOG_ERROR);
            return false;
        }

        $object->__object->get_by_id($id);

        if ($object->id == 0) {
            debug_add("Failed to load the record identified by {$id}, last Midgard error was:" . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return false;
        }
        if (!$object->can_do('midgard:read')) {
            debug_add("Failed to load object, read privilege on the " . $object::class . " {$object->guid} not granted for the current user.",
            MIDCOM_LOG_ERROR);
            $object->__object = new $object->__mgdschema_class_name__;
            return false;
        }

        $object->_on_loaded();
        return true;
    }

    /**
     * This call wraps the original get_by_guid call to provide access control.
     * The calling sequence is as with the corresponding constructor.
     */
    public static function get_by_guid(midcom_core_dbaobject $object, string $guid) : bool
    {
        if (   !midcom::get()->auth->admin
            && !midcom::get()->auth->acl->can_do_byguid('midgard:read', $guid, $object::class, midcom::get()->auth->acl->get_user_id())) {
            debug_add("Failed to load object, read privilege on the " . $object::class . " {$guid} not granted for the current user.", MIDCOM_LOG_ERROR);
            return false;
        }
        $object->__object->get_by_guid($guid);

        if ($object->id == 0) {
            debug_add("Failed to load the record identified by {$guid}, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return false;
        }
        $object->_on_loaded();
        return true;
    }

    /**
     * This call wraps the original get_by_guid call to provide access control.
     * The calling sequence is as with the corresponding constructor.
     */
    public static function get_by_path(midcom_core_dbaobject $object, string $path) : bool
    {
        $object->__object->get_by_path($path);

        if ($object->id == 0) {
            return false;
        }
        if (!$object->can_do('midgard:read')) {
            $object->__object = new $object->__mgdschema_class_name__;
            return false;
        }

        $object->_on_loaded();
        return true;
    }

    /**
     * "Pre-flight" checks for delete method
     *
     * Separated so that dbfactory->import() can reuse the code
     */
    public static function delete_pre_checks(midcom_core_dbaobject $object) : bool
    {
        if (!$object->id) {
            debug_add("Failed to delete object, object " . $object::class . " is non-persistent (empty ID).", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!$object->can_do('midgard:delete')) {
            debug_add("Failed to delete object, delete privilege on the " . $object::class . " {$object->guid} not granted for the current user.",
                MIDCOM_LOG_ERROR);
            midcom_connection::set_error(MGD_ERR_ACCESS_DENIED);
            return false;
        }
        return $object->_on_deleting();
    }
}
