<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is a help toolbar class.
 *
 * @package midcom.helper
 */
class midcom_helper_toolbar_help extends midcom_helper_toolbar
{
    /**
     * @var string
     */
    private $component;

    public function __construct(string $component)
    {
        $this->component = $component;
        $config = midcom::get()->config;
        parent::__construct($config->get('toolbars_help_style_class'), $config->get('toolbars_help_style_id'));
        $this->label = midcom::get()->i18n->get_string('help', 'midcom.admin.help');
        $this->add_commands();
    }

    private function add_commands()
    {
        $workflow = new midcom\workflow\viewer;
        $buttons = [
            $workflow->get_button("__ais/help/{$this->component}/", [
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('component help', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'h',
                MIDCOM_TOOLBAR_GLYPHICON => 'question',
            ]),
            [
                MIDCOM_TOOLBAR_URL => "http://midgard-project.org/midcom/",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('online documentation', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => ['target' => '_blank'],
                MIDCOM_TOOLBAR_GLYPHICON => 'globe',
            ],
            [
                MIDCOM_TOOLBAR_URL => "http://lists.midgard-project.org/listinfo/user",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('user forum', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => ['target' => '_blank'],
                MIDCOM_TOOLBAR_GLYPHICON => 'comments-o',
            ],
            [
                MIDCOM_TOOLBAR_URL => "https://github.com/flack/openpsa/issues",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('issue tracker', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => ['target' => '_blank'],
                MIDCOM_TOOLBAR_GLYPHICON => 'bug',
            ],
            $workflow->get_button('__ais/help/', [
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('about midgard', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_GLYPHICON => 'info',
            ])
        ];
        $this->add_items($buttons);
    }
}
