<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Comments view handler.
 *
 * This handler is a single handler which displays the thread for a given object GUID.
 * It checks for various commands in $_REQUEST during startup and processes them
 * if applicable. It relocates to the same page (using $_SERVER info) to prevent
 * duplicate request runs.
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_handler_view extends midcom_baseclasses_components_handler
{
    use net_nehmer_comments_handler;

    /**
     * The schema database to use.
     *
     * @var schemadb
     */
    private $_schemadb;

    /**
     * List of comments we are currently working with.
     *
     * @var Array
     */
    private $_comments = [];

    /**
     * A new comment just created for posting.
     *
     * @var net_nehmer_comments_comment
     */
    private $_new_comment;

    /**
     * The GUID of the object we're bound to.
     *
     * @var string GUID
     */
    private $_objectguid;

    /**
     * The controller used to post a new comment. Only set if we have a valid user.
     *
     * @var controller
     */
    private $_post_controller;

    /**
     * This datamanager instance is used to display an existing comment. only set
     * if there are actually comments to display.
     *
     * @var datamanager
     */
    private $_display_datamanager;

    private $custom_view;

    /**
     * Prepares the request data
     */
    private function _prepare_request_data()
    {
        $this->_request_data['comments'] = $this->_comments;
        $this->_request_data['objectguid'] = $this->_objectguid;
        $this->_request_data['post_controller'] = $this->_post_controller;
        $this->_request_data['display_datamanager'] = $this->_display_datamanager;
        $this->_request_data['custom_view'] = $this->custom_view;
    }

    /**
     * Prepares the _display_datamanager member.
     */
    private function _init_display_datamanager()
    {
        $this->_load_schemadb();
        $this->_display_datamanager = new datamanager($this->_schemadb);
    }

    /**
     * Loads the schemadb (unless it has already been loaded).
     */
    private function _load_schemadb()
    {
        if (!$this->_schemadb) {
            $this->_schemadb = schemadb::from_path($this->_config->get('schemadb'));

            if (   $this->_config->get('use_captcha')
                || (   !midcom::get()->auth->user
                    && $this->_config->get('use_captcha_if_anonymous'))) {
                $fields = $this->_schemadb->get('comment')->get('fields');
                $fields['captcha'] = [
                    'title' => $this->_l10n_midcom->get('captcha field title'),
                    'storage' => null,
                    'type' => 'captcha',
                    'widget' => 'captcha',
                    'widget_config' => $this->_config->get('captcha_config'),
                ];
                $this->_schemadb->get('comment')->set('fields', $fields);
            }

            if (   $this->_config->get('ratings_enable')
                && $this->_schemadb->get('comment')->has_field('rating')) {
                $field =& $this->_schemadb->get('comment')->get_field('rating');
                $field['hidden'] = false;
            }
        }
    }

    /**
     * Initializes a DM for posting.
     */
    private function _init_post_controller()
    {
        $this->_load_schemadb();

        $defaults = [];
        if (midcom::get()->auth->user) {
            $defaults['author'] = midcom::get()->auth->user->name;
        }

        $this->_new_comment = new net_nehmer_comments_comment();
        $this->_new_comment->objectguid = $this->_objectguid;
        //Proxy check
        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $this->_new_comment->ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $this->_new_comment->ip = $_SERVER['REMOTE_ADDR'];
        }

        if (midcom::get()->auth->user) {
            $this->_new_comment->status = net_nehmer_comments_comment::NEW_USER;
            $this->_new_comment->author = midcom::get()->auth->user->name;
        } else {
            $this->_new_comment->status = net_nehmer_comments_comment::NEW_ANONYMOUS;
        }

        if ($this->_config->get('enable_notify')) {
            $this->_new_comment->_send_notification = true;
        }

        $dm = new datamanager($this->_schemadb);
        $this->_post_controller = $dm
            ->set_defaults($defaults)
            ->set_storage($this->_new_comment)
            ->get_controller();
    }

    /**
     * Loads the comments, does any processing according to the state of the GET list.
     * On successful processing we relocate once to ourself.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_comments($handler_id, array $args, array &$data)
    {
        $this->_objectguid = $args[0];
        midcom::get()->cache->content->register($this->_objectguid);

        if ($handler_id == 'view-comments-nonempty') {
            $this->_comments = net_nehmer_comments_comment::list_by_objectguid_filter_anonymous(
                $this->_objectguid,
                $this->_config->get('items_to_show'),
                $this->_config->get('item_ordering'),
                $this->_config->get('paging')
            );
        } else {
            $this->_comments = net_nehmer_comments_comment::list_by_objectguid(
                $this->_objectguid,
                $this->_config->get('items_to_show'),
                $this->_config->get('item_ordering'),
                $this->_config->get('paging')
            );
        }

        if ($this->_config->get('paging') !== false) {
            $data['qb_pager'] = $this->_comments;
            $this->_comments = $this->_comments->execute();
        }

        if (   midcom::get()->auth->user
            || $this->_config->get('allow_anonymous')) {
            $this->_init_post_controller();
            $this->_process_post();
            // This might exit.
        }
        if ($this->_comments) {
            $this->_init_display_datamanager();
        }

        if (   $handler_id == 'view-comments-custom'
            && count($args) > 1) {
            midcom::get()->skip_page_style = true;
            $this->custom_view = $args[1];
        }

        $this->_prepare_request_data();
        midcom::get()->metadata->set_request_metadata($this->_get_last_modified(), $this->_objectguid);

        if (   isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            midcom::get()->skip_page_style = true;
        }
    }

    /**
     * Checks if a new post has been submitted.
     */
    private function _process_post()
    {
        if (   !midcom::get()->auth->user
            && !midcom::get()->auth->request_sudo('net.nehmer.comments')) {
            throw new midcom_error('We were anonymous but could not acquire SUDO privileges, aborting');
        }

        switch ($this->_post_controller->process()) {
            case 'save':
                // Check against comment spam
                $this->_new_comment->check_spam($this->_config);
                $formdata = $this->_post_controller->get_form_values();
                if (   $formdata['subscribe']
                    && midcom::get()->auth->user) {
                    // User wants to subscribe to receive notifications about this comments thread

                    // Get the object we're commenting
                    $parent = midcom::get()->dbfactory->get_object_by_guid($this->_objectguid);

                    // Sudo so we can update the parent object
                    if (midcom::get()->auth->request_sudo($this->_component)) {
                        // Save the subscription
                        $parent->set_parameter('net.nehmer.comments:subscription', midcom::get()->auth->user->guid, time());

                        // Return back from the sudo state
                        midcom::get()->auth->drop_sudo();
                    }
                }

                midcom::get()->cache->invalidate($this->_objectguid);
                // Fall-through intentional

            case 'cancel':
                if (!midcom::get()->auth->user) {
                    midcom::get()->auth->drop_sudo();
                }
                $this->_relocate_to_self();
                // This will exit();
        }
    }

    /**
     * Determines the last modified timestamp. It is the max out of all revised timestamps
     * of the comments (or 0 in case nothing was found).
     *
     * @return int Last-Modified Timestamp
     */
    private function _get_last_modified()
    {
        return array_reduce($this->_comments, function ($carry, net_nehmer_comments_comment $item) {
            return max($item->metadata->revised, $carry);
        }, 0);
    }

    /**
     * This is a shortcut for midcom::get()->relocate which relocates to the very same page we
     * are viewing right now, including all GET parameters we had in the original request.
     * We do this by taking the $_SERVER['REQUEST_URI'] variable.
     */
    private function _relocate_to_self()
    {
        midcom::get()->relocate($_SERVER['REQUEST_URI']);
    }

    /**
     * Display the comment list and the submit-comment form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_comments($handler_id, array &$data)
    {
        midcom_show_style('comments-header');
        if ($this->_comments) {
            midcom_show_style('comments-start');
            foreach ($this->_comments as $comment) {
                $this->_display_datamanager->set_storage($comment);
                $data['comment'] = $comment;
                $data['comment_toolbar'] = $this->populate_post_toolbar($comment);
                midcom_show_style('comments-item');

                if (   midcom::get()->auth->admin
                    || (   midcom::get()->auth->user
                        && $comment->can_do('midgard:delete'))) {
                    midcom_show_style('comments-admintoolbar');
                }
            }
            midcom_show_style('comments-end');
        } else {
            midcom_show_style('comments-nonefound');
        }

        if (   midcom::get()->auth->user
            || $this->_config->get('allow_anonymous')) {
            midcom_show_style('post-comment');
        } else {
            midcom_show_style('post-denied');
        }
        midcom_show_style('comments-footer');
    }
}
