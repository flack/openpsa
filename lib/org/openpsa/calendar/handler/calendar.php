<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_calendar extends midcom_baseclasses_components_handler
{
    /**
     * Initialization of the handler class
     */
    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        midcom::get()->uimessages->add_head_elements();
    }

    public function _handler_frontpage()
    {
        $selected_time = time();
        $view = $this->_config->get('start_view');
        if ($view == 'day') {
            $view = 'agendaDay';
        } elseif ($view != 'month') {
            $view = 'agendaWeek';
        }
        return new midcom_response_relocate($this->router->generate('calendar_view_mode_date', [
            'mode' => $view,
            'date' => date('Y-m-d', $selected_time)
        ]));
    }

    public function _handler_day(string $timestamp, array &$data)
    {
        $date = new DateTime($timestamp);
        $data['calendar_options'] = $this->get_calendar_options();
        $data['calendar_options']['defaultDate'] = $date->format('Y-m-d');
        $data['date'] = $date;
        org_openpsa_widgets_calendar::add_head_elements();
        return $this->show('show-agenda');
    }

    /**
     * Calendar view
     */
    public function _handler_calendar(array &$data)
    {
        $root_event = org_openpsa_calendar_interface::find_root_event();
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($root_event->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button('#', [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create event'),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus',
                MIDCOM_TOOLBAR_OPTIONS => [
                    'id' => 'openpsa_calendar_add_event',
                ]
            ]);
            if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_calendar_resource_dba::class)) {
                $buttons[] = $workflow->get_button($this->router->generate('new_resource'), [
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('resource')),
                    MIDCOM_TOOLBAR_GLYPHICON => 'television',
                ]);
            }
        }
        $buttons[] = $workflow->get_button($this->router->generate('filters_edit'), [
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('choose calendars'),
            MIDCOM_TOOLBAR_GLYPHICON => 'sliders',
        ]);

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => '#',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to'),
            MIDCOM_TOOLBAR_GLYPHICON => 'calendar',
            MIDCOM_TOOLBAR_OPTIONS => [
                'rel' => 'directlink',
                'id' => 'date-navigation',
            ]
        ];
        $this->_view_toolbar->add_items($buttons);

        $data['calendar_options'] = $this->get_calendar_options();
        org_openpsa_widgets_calendar::add_head_elements();
        midcom::get()->head->enable_jquery_ui(['datepicker']);
        return $this->show('show-calendar');
    }

    private function get_calendar_options() : array
    {
        $options = [
            'businessHours' => [
                'start' => $this->_config->get('day_start_time') . ':00',
                'end' => $this->_config->get('day_end_time') . ':00',
                'dow' => [1, 2, 3, 4, 5]
            ],
            'l10n' => ['cancel' => $this->_l10n_midcom->get('cancel')]
        ];

        $prefix = '/org.openpsa.widgets/fullcalendar-3.2.0/';
        $lang = midcom::get()->i18n->get_current_language();
        if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "locale/{$lang}.js")) {
            $lang = midcom::get()->i18n->get_fallback_language();
            if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "locale/{$lang}.js")) {
                $lang = false;
            }
        }

        if ($lang) {
            $options['lang'] = $lang;
        }

        return $options;
    }
}
