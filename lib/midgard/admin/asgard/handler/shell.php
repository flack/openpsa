<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shell interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_shell extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    public function _handler_shell(Request $request, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $controller = datamanager::from_schemadb($this->_config->get('schemadb_shell'))
            ->get_controller();

        switch ($controller->handle($request)) {
            case 'save':
                $data['code'] = $controller->get_form_values()['code'];
                break;

            case 'edit':
                foreach ($controller->get_errors() as $error) {
                    midcom::get()->uimessages->add($this->_l10n->get($this->_component), $error, 'error');
                }
        }

        $data['controller'] = $controller;
        $data['view_title'] = $this->_l10n->get('shell');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/shell.js');

        $data['asgard_toolbar'] = $this->_prepare_toolbar();

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('shell'), $data['view_title']);
        return $this->get_response();
    }

    private function _prepare_toolbar() : midgard_admin_asgard_toolbar
    {
        $toolbar = new midgard_admin_asgard_toolbar();
        $buttons = [
            [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('save in browser'),
                MIDCOM_TOOLBAR_GLYPHICON => 'floppy-o',
                MIDCOM_TOOLBAR_ACCESSKEY => 's',
                MIDCOM_TOOLBAR_OPTIONS => ['id' => 'save-script']
            ],
            [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('restore from browser'),
                MIDCOM_TOOLBAR_GLYPHICON => 'recycle',
                MIDCOM_TOOLBAR_ACCESSKEY => 'r',
                MIDCOM_TOOLBAR_OPTIONS => ['id' => 'restore-script']
            ],
            [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('clear all'),
                MIDCOM_TOOLBAR_GLYPHICON => 'trash',
                MIDCOM_TOOLBAR_ACCESSKEY => 'r',
                MIDCOM_TOOLBAR_OPTIONS => ['id' => 'clear-script']
            ]
        ];
        $toolbar->add_items($buttons);
        return $toolbar;
    }

    /**
     * @param array $data The local request data.
     */
    public function _show_shell(string $handler_id, array &$data)
    {
        if (!isset($_GET['ajax'])) {
            midcom_show_style('midgard_admin_asgard_shell');
        } else {
            midcom::get()->cache->content->enable_live_mode();
            ob_implicit_flush();
            midcom_show_style('midgard_admin_asgard_shell_runner');
            ob_implicit_flush(0);
        }
    }
}
