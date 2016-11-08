<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Comments welcome page handler
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_handler_admin extends midcom_baseclasses_components_handler
{
    /**
     * List of comments we are currently working with.
     *
     * @var Array
     */
    private $_comments = null;

    /**
     * The GUID of the object we're bound to.
     *
     * @var string GUID
     */
    private $_objectguid = null;

    /**
     * This datamanager instance is used to display an existing comment. only set
     * if there are actually comments to display.
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_display_datamanager = null;

    /**
     * Prepares the request data
     */
    private function _prepare_request_data()
    {
        $this->_request_data['comments'] = $this->_comments;
        $this->_request_data['objectguid'] = $this->_objectguid;
    }

    /**
     * Prepares the _display_datamanager member.
     */
    private function _init_display_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
        $this->_display_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
        $this->_request_data['display_datamanager'] = $this->_display_datamanager;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_welcome($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if (!$this->_topic->can_do('net.nehmer.comments:moderation'))
        {
            return new midcom_response_relocate('/');
        }
        $this->_request_data['topic'] = $this->_topic;
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
        $view_status = array();
        switch ($this->_request_data['handler'])
        {
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
                if ($this->_config->get('show_reported_abuse_as_normal'))
                {
                    $view_status[] = net_nehmer_comments_comment::REPORTED_ABUSE;
                }
                break;
            case 'latest_new':
                $this->_request_data['status_to_show'] = 'latest comments, only new';
                $view_status[] = net_nehmer_comments_comment::NEW_ANONYMOUS;
                $view_status[] = net_nehmer_comments_comment::NEW_USER;
                if ($this->_config->get('show_reported_abuse_as_normal'))
                {
                    $view_status[] = net_nehmer_comments_comment::REPORTED_ABUSE;
                }
                break;
            case 'latest_approved':
                $this->_request_data['status_to_show'] = 'latest comments, only approved';
                $view_status[] = net_nehmer_comments_comment::MODERATED;
                break;
        }

        $qb = new org_openpsa_qbpager('net_nehmer_comments_comment', 'net_nehmer_comments_comments');
        $qb->results_per_page = $this->_config->get('items_to_show');
        $qb->display_pages = $this->_config->get('paging');
        $qb->add_constraint('status', 'IN', $view_status);
        $qb->add_order('metadata.revised', 'DESC');

        return $qb->execute();
    }

    /**
     * Checks if a button of the admin toolbar was pressed.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_moderate_ajax($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('net.nehmer.comments:moderation');
        $this->_verify_post_data();

        $comment = new net_nehmer_comments_comment($_POST['guid']);
        if (!$comment->delete())
        {
            throw new midcom_error("Failed to delete comment GUID '{$_REQUEST['guid']}': " . midcom_connection::get_error_string());
        }

        midcom::get()->cache->invalidate($comment->objectguid);

        $this->_request_data['handler'] = $args[0];
        $comments = $this->_load_comments();
        if (!empty($comments))
        {
            $data['comment'] = end($comments);
            $this->_init_display_datamanager();
        }
        midcom::get()->skip_page_style = true;
    }

    private function _verify_post_data()
    {
        if (   !array_key_exists('action', $_POST)
            || !array_key_exists('guid', $_POST))
        {
            throw new midcom_error_notfound('Incomplete POST data');
        }
        if ($_POST['action'] !== 'action_delete')
        {
            throw new midcom_error_notfound('Unsupported action');
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_moderate_ajax($handler_id, array &$data)
    {
        if (!empty($data['comment']))
        {
            $this->_display_datamanager->autoset_storage($data['comment']);
            $data['comment_toolbar'] = $this->_master->_populate_post_toolbar($data['comment'], $data['handler']);
            midcom_show_style('admin-comments-item');
        }
        else
        {
            midcom_show_style('comments-nonefound');
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_moderate($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if (!$this->_topic->can_do('net.nehmer.comments:moderation'))
        {
            return new midcom_response_relocate('/');
        }

        $this->_request_data['handler'] = $args[0];

        $this->_comments = $this->_load_comments();
        if (!empty($this->_comments))
        {
            $this->_init_display_datamanager();
        }

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/net.nehmer.comments/moderate.js');

        net_nehmer_comments_viewer::add_head_elements();
        $this->_prepare_request_data();
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
        if ($this->_comments)
        {
            midcom_show_style('admin-comments-start');
            foreach ($this->_comments as $comment)
            {
                $this->_display_datamanager->autoset_storage($comment);
                $data['comment'] = $comment;
                $data['comment_toolbar'] = $this->_master->_populate_post_toolbar($comment, $data['handler']);
                midcom_show_style('admin-comments-item');
            }
            midcom_show_style('admin-comments-end');
        }
        else
        {
            midcom_show_style('comments-nonefound');
        }
        midcom_show_style('admin-end');
    }
}
