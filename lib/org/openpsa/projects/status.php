<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.projects
 */
class org_openpsa_projects_status extends org_openpsa_widgets_status
{
    /**
     *
     * @var org_openpsa_projects_task_dba
     */
    private $task;

    /**
     *
     * @var midcom_services_i18n_l10n
     */
    private $l10n;

    /**
     *
     * @param org_openpsa_projects_task_dba $task
     */
    public function __construct(org_openpsa_projects_task_dba $task)
    {
        $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.projects');
        $this->task = $task;
    }

    public function get_current_status() : string
    {
        return $this->l10n->get($this->get_status_class());
    }

    public function get_status_class() : string
    {
        return $this->task->status_type;
    }

    public function get_button() : string
    {
        return '';
    }

    public function get_history() : array
    {
        $entries = [];
        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', $this->task->id);
        $qb->add_order('timestamp', 'DESC');
        $qb->add_order('type', 'DESC');

        $fallback_creator = midcom_db_person::get_cached(1);
        foreach ($qb->execute() as $status_change) {
            $status_changer_label = $this->l10n->get('system');
            $target_person_label = $this->l10n->get('system');

            if (    $status_change->metadata->creator
                 && $status_change->metadata->creator != $fallback_creator->guid) {
                $status_changer = org_openpsa_widgets_contact::get($status_change->metadata->creator);
                $status_changer_label = $status_changer->show_inline();
            }

            if ($status_change->targetPerson) {
                $target_person = org_openpsa_widgets_contact::get($status_change->targetPerson);
                $target_person_label = $target_person->show_inline();
            }

            $entries[] = [
                'message' => sprintf($this->l10n->get($status_change->get_status_message()), $status_changer_label, $target_person_label),
                'timestamp' => $status_change->metadata->created
            ];
        }
        return $entries;
    }
}
