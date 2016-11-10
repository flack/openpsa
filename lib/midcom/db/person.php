<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Person record with framework support.
 *
 * @package midcom.db
 */
class midcom_db_person extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_person';

    public $autodelete_dependents = array
    (
        'midcom_db_eventmember' => 'uid',
        'midcom_db_member' => 'uid'
    );

    /**
     * Read-Only variable, consisting of "$firstname $lastname".
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $name = '';

    /**
     * Read-Only variable, consisting of "$lastname, $firstname".
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $rname = '';

    /**
     * Read-Only variable, consisting of a complete A HREF tag to homepage
     * if set.
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $homepagelink = '';

    /**
     * Read-Only variable, consisting of ta complete mailto A HREF tag to
     * the set email addres.
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $emaillink = '';

    /**
     * The default constructor will create an empty object. Optionally, you can pass
     * an object ID or GUID to the object which will then initialize the object with
     * the corresponding DB instance.
     *
     * @param mixed $id A valid object ID or GUID, omit for an empty object.
     */
    public function __construct($id = null)
    {
        if ($this->__mgdschema_class_name__ == 'midgard_person') {
            $this->__mgdschema_class_name__ = midcom::get()->config->get('person_class');
        }
        parent::__construct($id);
    }

    public function __set($property, $value)
    {
        parent::__set($property, $value);

        if (   $property == 'firstname'
            || $property == 'lastname'
            || $property == 'homepage'
            || $property == 'email') {
            $this->_update_computed_members();
        }
    }

    /**
     * Updates all computed members.
     */
    public function _on_loaded()
    {
        $this->_update_computed_members();
    }

    /**
     * Updates all computed members and adds a midgard:owner privilege for the person itself
     * on the record.
     */
    public function _on_created()
    {
        $this->set_privilege('midgard:owner', "user:{$this->guid}");

        $this->_update_computed_members();
    }

    /**
     * Synchronizes the $name, $rname, $emaillink and $homepagelink members
     * with the members they are based on.
     */
    private function _update_computed_members()
    {
        $firstname = trim($this->firstname);
        $lastname = trim($this->lastname);
        $this->name = trim("{$firstname} {$lastname}");
        $this->homepagelink = '';
        $this->emaillink = '';

        $this->rname = $lastname;
        if ($this->rname == '') {
            $this->rname = $firstname;
        } elseif ($firstname != '') {
            $this->rname .= ", {$firstname}";
        }

        if ($this->name == '') {
            $this->name = 'person #' . $this->id;
            $this->rname = 'person #' . $this->id;
        }

        if ($this->homepage != '') {
            $title = htmlspecialchars($this->name);
            $url = htmlspecialchars($this->homepage);
            $this->homepagelink = "<a href=\"{$url}\" title=\"{$title}\">{$url}</a>";
        }

        if ($this->email != '') {
            $title = htmlspecialchars($this->name);
            $url = htmlspecialchars($this->email);
            $this->emaillink = "<a href=\"mailto:{$url}\" title=\"{$title}\">{$url}</a>";
        }
    }

    public function get_label()
    {
        if ($this->rname) {
            return $this->rname;
        }
        if ($this->username) {
            return $this->username;
        }
        return '#' . $this->id;
    }

    /**
     * Adds a user to a given Midgard Group. Caller must ensure access permissions
     * are right.
     *
     * @param string $name The name of the group we should be added to.
     * @return boolean Indicating success.
     *
     * @todo Check if user is already assigned to the group.
     */
    function add_to_group($name)
    {
        $group = midcom::get()->auth->get_midgard_group_by_name($name);
        if (!$group) {
            debug_add("Failed to add the person {$this->id} to group {$name}, the group does not exist.", MIDCOM_LOG_WARN);
            return false;
        }
        $storage = $group->get_storage();
        $member = new midcom_db_member();
        $member->uid = $this->id;
        $member->gid = $storage->id;
        if (!$member->create()) {
            debug_add("Failed to add the person {$this->id} to group {$name}, object could not be created.", MIDCOM_LOG_WARN);
            debug_add('Last Midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            debug_print_r('Tried to create this object:', $member);
            return false;
        }
        return true;
    }
}
