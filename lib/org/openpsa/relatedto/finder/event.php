<?php
/**
 * @package org.openpsa.relatedto
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Find relatedto suspects
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_finder_event extends org_openpsa_relatedto_finder
{
    private org_openpsa_calendar_event_dba $event;

    public function __construct(org_openpsa_calendar_event_dba $event)
    {
        $this->event = $event;
    }

    public function process()
    {
        if (midcom::get()->componentloader->is_installed('org.openpsa.projects')) {
            // Do not seek if we have only one participant (gives a ton of results, most of them useless)
            if (count($this->event->participants) < 2) {
                debug_add("we have less than two participants, skipping seek");
            } elseif ($suspect_links = $this->find_in_projects()) {
                $this->save($suspect_links);
            }
        }
        if (midcom::get()->componentloader->is_installed('org.openpsa.sales')) {
            // if we have less than two participants, abort
            if (count($this->event->participants) > 2) {
                if ($suspect_links = $this->find_in_sales()) {
                    $this->save($suspect_links);
                }
            }
        }
    }

    /**
     * Current rule: all participants of event must be either manager, contact or resource in task
     * that overlaps in time with the event.
     */
    private function find_in_projects() : array
    {
        if ($cnt = $this->count_links($this->event->guid, org_openpsa_projects_task_dba::class, 'outgoing')) {
            debug_add("Found {$cnt} confirmed links already, skipping seek");
            return [];
        }

        $mc = org_openpsa_projects_task_resource_dba::new_collector();
        //Target task starts or ends inside given events window or starts before and ends after
        $mc->add_constraint('task.start', '<=', $this->event->end);
        $mc->add_constraint('task.end', '>=', $this->event->start);
        //Target task is active
        $mc->add_constraint('task.status', '<', org_openpsa_projects_task_status_dba::COMPLETED);
        $mc->add_constraint('task.status', '<>', org_openpsa_projects_task_status_dba::DECLINED);
        //Each event participant is either manager or member (resource/contact) in task
        $mc->begin_group('OR');
            $mc->add_constraint('task.manager', 'IN', array_keys($this->event->participants));
            $mc->add_constraint('person', 'IN', array_keys($this->event->participants));
        $mc->end_group();
        $suspects = $mc->get_values('task');
        if (empty($suspects)) {
            return [];
        }
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', array_unique($suspects));

        $defaults = $this->suspect_defaults($this->event, 'org.openpsa.calendar', 'outgoing');
        return $this->prepare_links($qb, 'org.openpsa.projects', $defaults);
    }

    /**
     * Current rule: all participants of event must be either manager,contact or resource in task
     * that overlaps in time with the event.
     */
    private function find_in_sales() : array
    {
        if ($cnt = $this->count_links($this->event->guid, [org_openpsa_sales_salesproject_dba::class, org_openpsa_sales_salesproject_deliverable_dba::class], 'incoming')) {
            debug_add("Found {$cnt} confirmed links already, skipping seek");
            return [];
        }

        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();

        // Target sales project starts or ends inside given events window or starts before and ends after
        $qb->add_constraint('start', '<=', $this->event->end);
        $qb->begin_group('OR');
            $qb->add_constraint('end', '>=', $this->event->start);
            $qb->add_constraint('end', '=', 0);
        $qb->end_group();

        //Target sales project is active
        $qb->add_constraint('state', '=', org_openpsa_sales_salesproject_dba::STATE_ACTIVE);

        //Each event participant is either manager or member (resource/contact) in task
        $mc = org_openpsa_projects_role_dba::new_collector('role', org_openpsa_sales_salesproject_dba::ROLE_MEMBER);
        $mc->add_constraint('person', 'IN', array_keys($this->event->participants));
        $ids = $mc->get_values('project');

        $qb->begin_group('OR');
            $qb->add_constraint('owner', 'IN', array_keys($this->event->participants));
            $qb->add_constraint('id', 'IN', $ids);
        $qb->end_group();

        $defaults = $this->suspect_defaults($this->event, 'org.openpsa.calendar', 'incoming');
        return $this->prepare_links($qb, 'org.openpsa.sales', $defaults);
    }
}