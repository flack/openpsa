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
     * The schema database to use.
     *
     * @var Array
     * @access private
     */
    var $_schemadb = null;

    /**
     * List of comments we are currently working with.
     *
     * @var Array
     * @access private
     */
    var $_comments = null;

    /**
     * A new comment just created for posting.
     *
     * @var net_nehmer_comments_comment
     * @access private
     */
    var $_new_comment = null;

    /**
     * The GUID of the object we're bound to.
     *
     * @var string GUID
     * @access private
     */
    var $_objectguid = null;

    /**
     * This datamanager instance is used to display an existing comment. only set
     * if there are actually comments to display.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_display_datamanager = null;

    var $custom_view = null;

    /**
     * Prepares the request data
     */
    function _prepare_request_data()
    {
        $this->_request_data['comments'] =& $this->_comments;
        $this->_request_data['objectguid'] =& $this->_objectguid;
        $this->_request_data['post_controller'] =& $this->_post_controller;
        $this->_request_data['display_datamanager'] =& $this->_display_datamanager;
        $this->_request_data['custom_view'] =& $this->custom_view;
    }

    /**
     * Prepares the _display_datamanager member.
     *
     * @access private
     */
    function _init_display_datamanager()
    {
        $this->_load_schemadb();
        $this->_display_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
    }

    /**
     * Loads the schemadb (unless it has already been loaded).
     */
    function _load_schemadb()
    {
        if (! $this->_schemadb)
        {
            $this->_schemadb = midcom_helper_datamanager2_schema::load_database(
                $this->_config->get('schemadb'));

            if (   $this->_config->get('use_captcha')
                || (   ! midcom::get('auth')->user
                    && $this->_config->get('use_captcha_if_anonymous')))
            {
                $this->_schemadb['comment']->append_field
                (
                    'captcha',
                    array
                    (
                        'title' => $this->_l10n_midcom->get('captcha field title'),
                        'storage' => null,
                        'type' => 'captcha',
                        'widget' => 'captcha',
                        'widget_config' => $this->_config->get('captcha_config'),
                    )
                );
            }

            if (   $this->_config->get('ratings_enable')
                && array_key_exists('rating', $this->_schemadb['comment']->fields))
            {
                $this->_schemadb['comment']->fields['rating']['hidden'] = false;
            }
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_welcome($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        if(!$this->_topic->can_do('net.nehmer.comments:moderation'))
        {
            midcom::get()->relocate('/');
        }
        $this->_request_data['topic'] = $this->_topic;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    function _show_welcome($handler_id, array &$data)
    {
        midcom_show_style('admin-start');
        midcom_show_style('admin-welcome');
        midcom_show_style('admin-end');
    }

    /**
     * Checks if a button of the admin toolbar was pressed. Detected by looking for the
     * net_nehmer_comment_adminsubmit value in the Request.
     *
     * As of this point, this tool assumes at least owner level privileges for all
     */
    function _process_admintoolbar()
    {
        if (! array_key_exists('net_nehmer_comment_adminsubmit', $_REQUEST))
        {
            // Nothing to do.
            return;
        }

        if (array_key_exists('action_delete', $_REQUEST))
        {
            $comment = new net_nehmer_comments_comment($_REQUEST['guid']);
            if (! $comment->delete())
            {
                throw new midcom_error("Failed to delete comment GUID '{$_REQUEST['guid']}': " . midcom_connection::get_error_string());
            }

            midcom::get('cache')->invalidate($comment->objectguid);
            $this->_relocate_to_self();
        }
    }

    /**
     * This is a shortcut for midcom::get()->relocate which relocates to the very same page we
     * are viewing right now, including all GET parameters we had in the original request.
     * We do this by taking the $_SERVER['REQUEST_URI'] variable.
     */
    function _relocate_to_self()
    {
        midcom::get()->relocate($_SERVER['REQUEST_URI']);
        // This will exit.
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_moderate($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        if (!$this->_topic->can_do('net.nehmer.comments:moderation'))
        {
            midcom::get()->relocate('/');
        }

        // This might exit.
        $this->_process_admintoolbar();

        $view_status = array();

        $this->_request_data['handler'] = $args[0];

        switch ($args[0])
        {
            case 'reported_abuse':
                $this->_request_data['status_to_show'] = 'reported abuse';
                $view_status[] = NET_NEHMER_COMMENTS_REPORTED_ABUSE;
              break;
            case 'abuse':
                $this->_request_data['status_to_show'] = 'abuse';
                $view_status[] = NET_NEHMER_COMMENTS_ABUSE;
              break;
            case 'junk':
                $this->_request_data['status_to_show'] = 'junk';
                $view_status[] = NET_NEHMER_COMMENTS_JUNK;
              break;
            case 'latest':
                $this->_request_data['status_to_show'] = 'latest comments';
                $view_status[] = NET_NEHMER_COMMENTS_NEW;
                $view_status[] = NET_NEHMER_COMMENTS_NEW_ANONYMOUS;
                $view_status[] = NET_NEHMER_COMMENTS_NEW_USER;
                $view_status[] = NET_NEHMER_COMMENTS_MODERATED;
                if ($this->_config->get('show_reported_abuse_as_normal'))
                {
                    $view_status[] = NET_NEHMER_COMMENTS_REPORTED_ABUSE;
                }
              break;
            case 'latest_new':
                $this->_request_data['status_to_show'] = 'latest comments, only new';
                $view_status[] = NET_NEHMER_COMMENTS_NEW;
                $view_status[] = NET_NEHMER_COMMENTS_NEW_ANONYMOUS;
                $view_status[] = NET_NEHMER_COMMENTS_NEW_USER;
                if ($this->_config->get('show_reported_abuse_as_normal'))
                {
                    $view_status[] = NET_NEHMER_COMMENTS_REPORTED_ABUSE;
                }
              break;
            case 'latest_approved':
                $this->_request_data['status_to_show'] = 'latest comments, only approved';
                $view_status[] = NET_NEHMER_COMMENTS_MODERATED;
              break;
        }

        $qb = new org_openpsa_qbpager('net_nehmer_comments_comment', 'net_nehmer_comments_comments');
        $qb->results_per_page = $this->_config->get('items_to_show');
        $qb->display_pages = $this->_config->get('paging');
        $qb->add_constraint('status', 'IN', $view_status);
        $qb->add_order('metadata.revised', 'DESC');

        $this->_comments = $qb->execute();

        if ($this->_comments)
        {
            $this->_init_display_datamanager();
        }
        $this->_prepare_request_data();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    function _show_moderate($handler_id, array &$data)
    {
        midcom_show_style('admin-start');
        if ($this->_comments)
        {
            midcom_show_style('admin-comments-start');
            foreach ($this->_comments as $comment)
            {
                $this->_display_datamanager->autoset_storage($comment);
                $data['comment'] =& $comment;
                $data['comment_toolbar'] = $this->_master->_populate_post_toolbar($comment);
                midcom_show_style('admin-comments-item');

                if (   midcom::get('auth')->admin
                    || (   midcom::get('auth')->user
                        && $comment->can_do('midgard:delete')))
                {
                    midcom_show_style('admin-comments-admintoolbar');
                }
            }
            midcom_show_style('admin-comments-end');
        }
        else
        {
            midcom_show_style('comments-nonefound');
        }
        midcom_show_style('admin-list-end');
        midcom_show_style('admin-end');
    }
}
?>