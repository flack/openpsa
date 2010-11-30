<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: object.php 22991 2009-07-23 16:09:46Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Temporary Database Object
 *
 * Controlled by the temporary object service, you should never create instances
 * of this type directly.
 *
 * @see midcom_services_tmp
 * @package midcom
 */
class midcom_core_temporary_object extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midcom_core_temporary_object_db';

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    /**
     * These objects have no restrictions whatsoever directly assigned to them.
     * This allows you to assign further privileges to the temporary object during
     * data entry for example (and then copy these privileges to the live object).
     */
    function get_class_magic_default_privileges()
    {
        return Array
        (
            'EVERYONE' => Array
            (
                'midgard:owner' => MIDCOM_PRIVILEGE_ALLOW,
            ),
            'ANONYMOUS' => Array(),
            'USERS' => Array(),
        );
    }

    /**
     * Update the object timestamp.
     */
    public function _on_creating()
    {
        $this->timestamp = time();
        return true;
    }

    /**
     * Update the object timestamp.
     */
    public function _on_updating()
    {
        $this->timestamp = time();
        return true;
    }

    /**
     * Transfers all parameters attachments and privileges on the current object to another
     * existing Midgard object. You need to have midgard:update, midgard:parameter,
     * midgard:privileges and midgard:attachments privileges on the target object,
     * which must be a persistent MidCOM DBA class instance. (For ease of use, it is recommended
     * to have midgard:owner rights for the target object, which includes the above
     * privileges).
     *
     * <b>Important notes:</b>
     *
     * All records in question will just be moved, not copied!
     * Also, there will be <i>no</i> integrity checking in terms of already existing
     * parameters etc. This feature is mainly geared towards preparing a freshly
     * created final object with the data associated with this temporary object.
     *
     * Any invalid object / missing privilege will trigger a generate_error.
     *
     * @param midcom_dba_object $object The object to transfer the extensions to.
     */
    function move_extensions_to_object($object)
    {
        if (! $_MIDCOM->dbclassloader->is_midcom_db_object($object))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'The object passed is no valid for move_extensions_to_object.');
            // This will exit.
        }

        // Validate Privileges
        $object->require_do('midgard:update');
        $object->require_do('midgard:privileges');
        $object->require_do('midgard:parameters');
        $object->require_do('midgard:attachments');

        // Copy parameters from temporary object
        $parameters = $this->list_parameters();

        foreach ($parameters as $domain => $array)
        {
            foreach ($array as $name => $value)
            {
                $object->set_parameter($domain, $name, $value);
            }
        }

        // Move attachments from temporary object
        $attachments = $this->list_attachments();
        foreach ($attachments as $attachment)
        {
            $attachment->parentguid = $object->guid;
            $attachment->update();
        }

        // Privileges are moved using the DBA API as well.
        $privileges = $this->get_privileges();
        if ($privileges)
        {
            foreach ($privileges as $privilege)
            {
                $privilege->set_object($object);
                $privilege->store();
            }
        }
    }

    /**
     * Autopurge after delete
     */
    public function _on_deleted()
    {
        if (!method_exists($this, 'purge'))
        {
            return;
        }
        $this->purge();
    }
}
?>