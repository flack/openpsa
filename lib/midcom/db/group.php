<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Group record with framework support.
 *
 * @property string $name Path name of the group
 * @property string $official Official name of the group
 * @property string $street Street address of the group
 * @property string $postcode Zip code of the group
 * @property string $city City of the group
 * @property string $country Country of the group
 * @property string $homepage Homepage URL of the group
 * @property string $email Email of the group
 * @property string $phone Phone number of the group
 * @property string $fax Fax number of the group
 * @property string $extra Additional information about the group
 * @property integer $owner Group the group is under
 * @package midcom.db
 */
class midcom_db_group extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_group';

    public $autodelete_dependents = [
        midcom_db_member::class => 'gid'
    ];

    public function get_label()
    {
        return $this->official;
    }

    /**
     * Updates all computed members.
     */
    public function _on_loaded()
    {
        if (empty($this->official)) {
            $this->official = $this->name ?: "Group #{$this->id}";
        }
    }

    /**
     * Add the given person to this group. The current user must have
     * midgard:create privileges on this object for this to succeed. If the person is
     * already a member of this group, nothing is done.
     *
     * @param midcom_db_person $person The person to add.
     * @return boolean Indicating success.
     */
    public function add_member($person) : bool
    {
        $this->require_do('midgard:create');

        if ($this->is_member($person)) {
            return true;
        }

        $member = new midcom_db_member();
        $member->gid = $this->id;
        $member->uid = $person->id;
        if (!$member->create()) {
            return false;
        }

        // Adjust privileges, owner is the group in question.
        $member->set_privilege('midgard:owner', "group:{$this->guid}");
        $member->unset_privilege('midgard:owner');

        return true;
    }

    /**
     * Check whether the given user is a member of this group.
     *
     * @param midcom_db_person $person The person to check.
     * @return boolean Indicating membership.
     */
    function is_member($person) : bool
    {
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $this->id);
        $qb->add_constraint('uid', '=', $person->id);
        return $qb->count() > 0;
    }
}
