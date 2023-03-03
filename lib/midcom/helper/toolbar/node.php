<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is a node (topic) toolbar class.
 *
 * @package midcom.helper
 */
class midcom_helper_toolbar_node extends midcom_helper_toolbar_view
{
    private midcom_db_topic $topic;

    public function __construct(midcom_db_topic $topic)
    {
        $this->topic = $topic;
        $config = midcom::get()->config;
        parent::__construct($config->get('toolbars_node_style_class'), $config->get('toolbars_node_style_id'));
        $this->label = midcom::get()->i18n->get_string('folder', 'midcom');
    }

    protected function _check_index($index, bool $raise_error = true) : ?int
    {
        $this->add_commands();
        return parent::_check_index($index, $raise_error);
    }

    public function render() : string
    {
        $this->add_commands();
        return parent::render();
    }

    public function is_rendered() : bool
    {
        $this->add_commands();
        return parent::is_rendered();
    }

    public function add_item(array $item, $before = -1)
    {
        $this->add_commands();
        parent::add_item($item, $before);
    }

    private function add_commands()
    {
        if (!empty($this->items) || empty($this->topic->id)) {
            return;
        }
        $buttons = [];
        $workflow = new midcom\workflow\datamanager;
        if (   $this->topic->can_do('midgard:update')
            && $this->topic->can_do('midcom.admin.folder:topic_management')) {
            $buttons[] = $workflow->get_button("__ais/folder/edit/", [
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit folder', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'g',
            ]);
            $buttons[] = $workflow->get_button("__ais/folder/metadata/{$this->topic->guid}/", [
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit folder metadata', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_GLYPHICON => 'database',
            ]);
            // Allow to move other than root folder
            if ($this->topic->guid !== midcom::get()->config->get('midcom_root_topic_guid')) {
                $viewer = new midcom\workflow\viewer;
                $buttons[] = $viewer->get_button("__ais/folder/move/{$this->topic->guid}/", [
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('move', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'arrows',
                ]);
            }

            $viewer = new midcom\workflow\viewer;
            $buttons[] = $viewer->get_button("__ais/folder/order/", [
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('order navigation', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_GLYPHICON => 'sort',
                MIDCOM_TOOLBAR_ACCESSKEY => 'o',
            ]);

            $buttons[] = [
                MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/object/open/{$this->topic->guid}/",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('manage object', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_GLYPHICON => 'cog',
                MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin') && midcom::get()->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'),
            ];
        }
        $buttons = array_merge($buttons, $this->get_approval_controls($this->topic, false));
        if (   $this->topic->can_do('midcom.admin.folder:template_management')
            && midcom::get()->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin')) {
            $enabled = false;
            $styleeditor_url = '';
            if ($this->topic->style != '') {
                if ($style_id = midcom_db_style::id_from_path($this->topic->style)) {
                    try {
                        $style = midcom_db_style::get_cached($style_id);
                        $styleeditor_url = midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$style->guid}/";
                        $enabled = true;
                    } catch (midcom_error $e) {
                        $e->log();
                    }
                }
            }

            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $styleeditor_url,
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit layout template', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
                MIDCOM_TOOLBAR_ACCESSKEY => 't',
                MIDCOM_TOOLBAR_ENABLED => $enabled,
            ];
        }

        if ($this->topic->can_do('midcom.admin.folder:topic_management')) {
            if ($this->topic->can_do('midgard:create')) {
                $buttons[] = $workflow->get_button("__ais/folder/create/", [
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('create subfolder', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'folder',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'f',
                ]);
            }
            if (   $this->topic->guid !== midcom::get()->config->get('midcom_root_topic_guid')
                && $this->topic->can_do('midgard:delete')) {
                $workflow = new midcom\workflow\delete(['object' => $this->topic, 'recursive' => true]);
                $buttons[] = $workflow->get_button("__ais/folder/delete/", [
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('delete folder', 'midcom.admin.folder')
                ]);
            }
        }
        $this->items = array_map([$this, 'clean_item'], $buttons);
    }
}
