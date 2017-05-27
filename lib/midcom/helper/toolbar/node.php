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
    /**
     *
     * @var midcom_db_topic
     */
    private $topic;

    public function __construct(midcom_db_topic $topic)
    {
        $this->topic = $topic;
        $config = midcom::get()->config;
        parent::__construct($config->get('toolbars_node_style_class'), $config->get('toolbars_node_style_id'));
        $this->label = midcom::get()->i18n->get_string('folder', 'midcom');
        $this->add_commands();
    }

    private function add_commands()
    {
        $topics = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URLTOPICS);
        $urltopic = end($topics);
        if (!$urltopic) {
            $urltopic = $this->topic;
        }
        $buttons = array();
        $workflow = new midcom\workflow\datamanager2;
        if (   $this->topic->can_do('midgard:update')
            && $this->topic->can_do('midcom.admin.folder:topic_management')) {
            $buttons[] = $workflow->get_button("__ais/folder/edit/", array(
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit folder', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'g',
            ));
            $buttons[] = $workflow->get_button("__ais/folder/metadata/{$urltopic->guid}/", array(
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit folder metadata', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/metadata.png',
            ));
        }

        if (   $urltopic->can_do('midgard:update')
            && $urltopic->can_do('midcom.admin.folder:topic_management')) {
            // Allow to move other than root folder
            if ($urltopic->guid !== midcom::get()->config->get('midcom_root_topic_guid')) {
                $viewer = new midcom\workflow\viewer;
                $buttons[] = $viewer->get_button("__ais/folder/move/{$urltopic->guid}/", array(
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('move', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
                ));
            }
        }

        if (   $this->topic->can_do('midgard:update')
            && $this->topic->can_do('midcom.admin.folder:topic_management')) {
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "__ais/folder/order/",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('order navigation', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/topic-score.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'o',
            );

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/object/open/{$this->topic->guid}/",
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('manage object', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin', 'midgard.admin.asgard') && midcom::get()->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'),
            );
        }
        $buttons = array_merge($buttons, $this->get_approval_controls($this->topic, false));
        if (   $this->topic->can_do('midcom.admin.folder:template_management')
            && midcom::get()->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin')) {
            $enabled = false;
            $styleeditor_url = '';
            if ($this->topic->style != '') {
                if ($style_id = midcom::get()->style->get_style_id_from_path($this->topic->style)) {
                    try {
                        $style = midcom_db_style::get_cached($style_id);
                        $styleeditor_url = midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$style->guid}/";
                        $enabled = true;
                    } catch (midcom_error $e) {
                        $e->log();
                    }
                }
            }

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => $styleeditor_url,
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit layout template', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/text-x-generic-template.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 't',
                MIDCOM_TOOLBAR_ENABLED => $enabled,
            );
        }

        if (   $this->topic->can_do('midgard:create')
            && $this->topic->can_do('midcom.admin.folder:topic_management')) {
            $buttons[] = $workflow->get_button("__ais/folder/create/", array(
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('create subfolder', 'midcom.admin.folder'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'f',
            ));
        }
        if (   $urltopic->guid !== midcom::get()->config->get('midcom_root_topic_guid')
            && $urltopic->can_do('midgard:delete')
            && $urltopic->can_do('midcom.admin.folder:topic_management')) {
            $workflow = new midcom\workflow\delete(array('object' => $urltopic, 'recursive' => true));
            $buttons[] = $workflow->get_button("__ais/folder/delete/", array(
                MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('delete folder', 'midcom.admin.folder')
            ));
        }
        $this->add_items($buttons);
    }
}
