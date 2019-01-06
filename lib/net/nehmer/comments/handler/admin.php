<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Comments welcome page handler
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_handler_admin extends midcom_baseclasses_components_handler
{
    use net_nehmer_comments_handler;

    /**
     * This datamanager instance is used to display an existing comment. only set
     * if there are actually comments to display.
     *
     * @var datamanager
     */
    private $_display_datamanager;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        $this->_topic->require_do('net.nehmer.comments:moderation');
    }

    /**
     * Prepares the _display_datamanager member.
     */
    private function _init_display_datamanager()
    {
        $this->_display_datamanager = datamanager::from_schemadb($this->_config->get('schemadb'));
        $this->_request_data['display_datamanager'] = $this->_display_datamanager;
    }

    /**
     *
     * @param array &$data The local request data.
     */
    public function _handler_welcome(array &$data)
    {
        $data['topic'] = $this->_topic;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_welcome($handler_id, array &$data)
    {
        midcom_show_style('admin-start');
        midcom_show_style('admin-welcome');
        midcom_show_style('admin-end');
    }

    private function _load_comments()
    {
        $view_status = [];
        switch ($this->_request_data['handler']) {
            case 'reported_abuse':
                $this->_request_data['status_to_show'] = 'reported abuse';
                $view_status[] = net_nehmer_comments_comment::REPORTED_ABUSE;
                break;
            case 'abuse':
                $this->_request_data['status_to_show'] = 'abuse';
                $view_status[] = net_nehmer_comments_comment::ABUSE;
                break;
            case 'junk':
                $this->_request_data['status_to_show'] = 'junk';
                $view_status[] = net_nehmer_comments_comment::JUNK;
                break;
            case 'latest':
                $this->_request_data['status_to_show'] = 'latest comments';
                $view_status[] = net_nehmer_comments_comment::NEW_ANONYMOUS;
                $view_status[] = net_nehmer_comments_comment::NEW_USER;
                $view_status[] = net_nehmer_comments_comment::MODERATED;
                if ($this->_config->get('show_reported_abuse_as_normal')) {
                    $view_status[] = net_nehmer_comments_comment::REPORTED_ABUSE;
                }
                break;
            case 'latest_new':
                $this->_request_data['status_to_show'] = 'latest comments, only new';
                $view_status[] = net_nehmer_comments_comment::NEW_ANONYMOUS;
                $view_status[] = net_nehmer_comments_comment::NEW_USER;
                if ($this->_config->get('show_reported_abuse_as_normal')) {
                    $view_status[] = net_nehmer_comments_comment::REPORTED_ABUSE;
                }
                break;
            case 'latest_approved':
                $this->_request_data['status_to_show'] = 'latest comments, only approved';
                $view_status[] = net_nehmer_comments_comment::MODERATED;
                break;
        }

        $qb = new org_openpsa_qbpager(net_nehmer_comments_comment::class, 'net_nehmer_comments_comments');
        $qb->results_per_page = $this->_config->get('items_to_show');
        $qb->display_pages = $this->_config->get('paging');
        $qb->add_constraint('status', 'IN', $view_status);
        $qb->add_order('metadata.revised', 'DESC');

        return $qb->execute();
    }

    /**
     * Checks if a button of the admin toolbar was pressed.
     *
     * @param string $status The moderation status
     * @param array &$data The local request data.
     */
    public function _handler_moderate_ajax(Request $request, $status, array &$data)
    {
        if (   !$request->request->has('action')
            || !$request->request->has('guid')) {
            throw new midcom_error_notfound('Incomplete POST data');
        }
        if ($request->request->get('action') !== 'action_delete') {
            throw new midcom_error_notfound('Unsupported action');
        }

        $comment = new net_nehmer_comments_comment($request->request->get('guid'));
        if (!$comment->delete()) {
            throw new midcom_error("Failed to delete comment GUID '{$_REQUEST['guid']}': " . midcom_connection::get_error_string());
        }

        midcom::get()->cache->invalidate($comment->objectguid);

        $this->_request_data['handler'] = $status;
        $comments = $this->_load_comments();
        if (!empty($comments)) {
            $data['comment'] = end($comments);
            $this->_init_display_datamanager();
        }
        midcom::get()->skip_page_style = true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_moderate_ajax($handler_id, array &$data)
    {
        if (!empty($data['comment'])) {
            $this->_display_datamanager->set_storage($data['comment']);
            $data['comment_toolbar'] = $this->populate_post_toolbar($data['comment'], $data['handler']);
            midcom_show_style('admin-comments-item');
        }
    }

    /**
     * @param string $status The moderation status
     * @param array &$data The local request data.
     */
    public function _handler_moderate($status, array &$data)
    {
        $data['handler'] = $status;

        $data['comments'] = $this->_load_comments();
        if (!empty($data['comments'])) {
            $this->_init_display_datamanager();
        }

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/net.nehmer.comments/moderate.js');

        net_nehmer_comments_viewer::add_head_elements();
        $this->add_breadcrumb('', $this->_l10n->get($data['status_to_show']));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_moderate($handler_id, array &$data)
    {
        midcom_show_style('admin-start');
        if ($data['comments']) {
            midcom_show_style('admin-comments-start');
            foreach ($data['comments'] as $comment) {
                $data['comment'] = $comment;
                $this->_show_moderate_ajax($handler_id, $data);
            }
            midcom_show_style('admin-comments-end');
        }
        midcom_show_style('admin-end');
    }
}
