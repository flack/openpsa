<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * List groups
 *
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_group_list extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    /**
     * Populate breadcrumb
     */
    private function _update_breadcrumb($handler_id)
    {
        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get('midgard.admin.user'));
        $this->add_breadcrumb($this->router->generate('group_list'), $this->_l10n->get('groups'));

        if (preg_match('/group_move$/', $handler_id)) {
            $this->add_breadcrumb($this->router->generate('group_edit', ['guid' => $this->_request_data['group']->guid]), $this->_request_data['group']->official);
            $this->add_breadcrumb($this->router->generate('group_move', ['guid' => $this->_request_data['group']->guid]), $this->_l10n_midcom->get('move'));
        }
    }

    /**
     * Handle the moving of a group phase
     */
    public function _handler_move(Request $request, string $handler_id, string $guid, array &$data)
    {
        $data['group'] = new midcom_db_group($guid);

        if ($request->request->has('f_cancel')) {
            return new midcom_response_relocate($this->router->generate('group_edit', ['guid' => $guid]));
        }

        if ($request->request->has('f_submit')) {
            $data['group']->owner = $request->request->getInt('midgard_admin_user_move_group');

            if ($data['group']->update()) {
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), $this->_l10n_midcom->get('updated'));
                return new midcom_response_relocate($this->router->generate('group_edit', ['guid' => $guid]));
            }
            debug_add('Failed to update the group, last error was '. midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            debug_print_r('We operated on this object', $data['group'], MIDCOM_LOG_ERROR);

            throw new midcom_error('Failed to update the group, see error level log for details');
        }

        $data['view_title'] = sprintf($this->_l10n->get('move %s'), $data['group']->official);

        $this->_update_breadcrumb($handler_id);
        return $this->get_response();
    }

    /**
     * Show the moving of a group phase
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_move($handler_id, array &$data)
    {
        midcom_show_style('midgard-admin-user-group-list-start');

        // Show the form headers
        midcom_show_style('midgard-admin-user-move-group-start');

        // Show the recursive listing
        self::list_groups(0, $data, true);

        // Show the form footers
        midcom_show_style('midgard-admin-user-move-group-end');

        midcom_show_style('midgard-admin-user-group-list-end');
    }

    /**
     * Handle the listing phase
     */
    public function _handler_list(string $handler_id, array &$data)
    {
        $data['view_title'] = $this->_l10n->get('groups');

        $data['asgard_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('group_create'),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create group'),
            MIDCOM_TOOLBAR_GLYPHICON => 'users',
        ]);

        $this->_update_breadcrumb($handler_id);
        return $this->get_response();
    }

    /**
     * Show the group listing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('midgard-admin-user-group-list-start');

        // Show the recursive listing
        self::list_groups(0, $data);

        midcom_show_style('midgard-admin-user-group-list-end');
    }

    /**
     * Internal helper for showing the groups recursively
     *
     * @param int $id
     * @param array $data
     */
    public static function list_groups($id, array &$data, bool $move = false)
    {
        $mc = midcom_db_group::new_collector('owner', (int) $id);

        // Set the order
        $mc->add_order('metadata.score', 'DESC');
        $mc->add_order('official');
        $mc->add_order('name');

        $groups = $mc->get_rows(['name', 'official', 'id']);

        // Hide empty groups
        if (empty($groups)) {
            return;
        }

        // Group header
        midcom_show_style('midgard-admin-user-group-list-header');

        // Show the groups
        foreach ($groups as $guid => $array) {
            $data['guid'] = $guid;
            $data['id'] = $array['id'];
            $data['name'] = $array['name'];
            $data['title'] = $array['official'];

            if (empty($data['title'])) {
                $data['title'] = $data['name'];
                if (empty($data['title'])) {
                    $data['title'] = $data['l10n_midcom']->get('unknown');
                }
            }

            // Show the group
            if ($move) {
                // Prevent moving owner to any of its children
                $data['disabled'] = self::belongs_to($data['id'], $data['group']->id);

                midcom_show_style('midgard-admin-user-group-list-group-move');
            } else {
                midcom_show_style('midgard-admin-user-group-list-group');
            }
        }

        // Group footer
        midcom_show_style('midgard-admin-user-group-list-footer');
    }

    /**
     * Internal helper to check if the requested group belongs to the haystack
     */
    public static function belongs_to(int $id, int $owner) : bool
    {
        if ($id === $owner) {
            return true;
        }
        $qb = midcom_db_group::new_query_builder();
        $qb->add_constraint('id', '=', $id);
        $qb->add_constraint('owner', 'INTREE', $owner);
        return $qb->count() > 0;
    }
}
