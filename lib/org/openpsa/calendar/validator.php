<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Eventmember conflict validator
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_validator
{
    private org_openpsa_calendar_event_dba $event;

    private midcom_services_i18n_l10n $l10n;

    public function __construct(org_openpsa_calendar_event_dba $event, midcom_services_i18n_l10n $l10n)
    {
        $this->event = $event;
        $this->l10n = $l10n;
    }

    /**
     * Validate create/edit forms
     *
     * @return mixed Array with error message or true on success
     */
    public function validate(array $input) : array|true
    {
        $this->event->busy = $input['busy'];
        $this->event->participants = array_flip($input['participants']);
        $this->event->start = $input['start'] + 1;
        $this->event->end = $input['end'];

        $mgr = new org_openpsa_calendar_conflictmanager($this->event);

        if (!$mgr->run($this->event->rob_tentative)) {
            return [
                'participants' => $this->l10n->get('event conflict') . "\n" . $this->get_message($mgr)
            ];
        }

        return true;
    }

    private function get_message(org_openpsa_calendar_conflictmanager $mgr) : string
    {
        $formatter = $this->l10n->get_formatter();
        $message = '<ul>';
        foreach ($mgr->busy_members as $uid => $events) {
            $message .= '<li>' . org_openpsa_widgets_contact::get($uid)->show_inline();
            $message .= '<ul>';
            foreach ($events as $event) {
                $message .= '<li>' . $formatter->timeframe($event->start, $event->end) . ': ' . $event->title . '</li>';
            }
            $message .= '</li>';
            $message .= '</ul>';
        }
        return $message . '</ul>';
    }
}