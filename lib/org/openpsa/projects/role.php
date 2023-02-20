<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property integer $project
 * @property integer $role
 * @property string $description
 * @property integer $person
 * @property integer $status
 * @package org.openpsa.projects
 */
class org_openpsa_projects_role_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_role';

    public bool $_use_rcs = false;

    public static function add(int $project, int $person, int $role) : bool
    {
        $mc = self::new_collector('project', $project);
        $mc->add_constraint('role', '=', $role);
        $mc->add_constraint('person', '=', $person);
        $mc->execute();
        if ($mc->count() > 0) {
            //Resource is already present, aborting silently
            return true;
        }

        $new_role = new self();
        $new_role->person = $person;
        $new_role->role = $role;
        $new_role->project = $project;
        return $new_role->create();
    }

    /**
     * Returns true for NO existing duplicates
     */
    public function check_duplicates() : bool
    {
        $qb = new midgard_query_builder('org_openpsa_role');
        $qb->add_constraint('person', '=', $this->person);
        $qb->add_constraint('project', '=', $this->project);
        $qb->add_constraint('role', '=', $this->role);

        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }

        return $qb->count() == 0;
    }

    public function _on_creating() : bool
    {
        return $this->check_duplicates();
    }

    public function _on_updating() : bool
    {
        return $this->check_duplicates();
    }
}
