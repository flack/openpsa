<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Comments main comment class
 *
 * Comments link up to the object they refer to.
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_comment extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nehmer_comments_comment_db';

    // New messages enter at 4, and can be lowered or raised
    const JUNK = 1;
    const ABUSE = 2;
    const REPORTED_ABUSE = 3;
    const NEW_ANONYMOUS = 4;
    const NEW_USER = 5;
    const MODERATED = 6;

    var $_send_notification = false;

    public $_sudo_requested = false;

    /**
     * DBA magic defaults which assign write privileges for all USERS, so that they can
     * add new comments at will.
     */
    public function get_class_magic_default_privileges()
    {
        return array
        (
            'EVERYONE' => array(),
            'ANONYMOUS' => array(),
            'USERS' => array
            (
                'midgard:create' => MIDCOM_PRIVILEGE_ALLOW
            ),
        );
    }

    /**
     * Link to the parent object specified in the objectguid field.
     */
    public function get_parent_guid_uncached()
    {
        return $this->objectguid;
    }

    /**
     * Returns a list of comments applicable to a given object, ordered by creation
     * date.
     *
     * @param guid $guid The GUID of the object to bind to.
     * @return net_nehmer_comments_comment[] List of applicable comments.
     */
    public static function list_by_objectguid($guid, $limit = false, $order = 'ASC', $paging = false, $status = false)
    {
        $qb = self::_prepare_query($guid, $status, $paging);

        if (   $limit
            && !$paging) {
            $qb->set_limit($limit);
        }

        $qb->add_order('metadata.published', $order);

        if ($paging !== false) {
            return $qb;
        }

        return $qb->execute();
    }

    /**
     * Returns a list of comments applicable to a given object
     * not diplaying empty comments or anonymous posts,
     * ordered by creation date.
     *
     * @param guid $guid The GUID of the object to bind to.
     * @return net_nehmer_comments_comment[] List of applicable comments.
     */
    public static function list_by_objectguid_filter_anonymous($guid, $limit = false, $order = 'ASC', $paging = false, $status = false)
    {
        $qb = self::_prepare_query($guid, $status, $paging);
        $qb->add_constraint('author', '<>', '');
        $qb->add_constraint('content', '<>', '');

        if (   $limit
            && !$paging) {
            $qb->set_limit($limit);
        }

        $qb->add_order('metadata.published', $order);

        if ($paging !== false) {
            return $qb;
        }

        return $qb->execute();
    }

    /**
     * Returns the number of comments associated with a given object. This is intended for
     * outside usage to render stuff like "15 comments". The count is executed unchecked.
     *
     * @return int Number of comments matching a given result.
     */
    public static function count_by_objectguid($guid, $status = false)
    {
        $qb = self::_prepare_query($guid, $status);
        return $qb->count_unchecked();
    }

    /**
     * Returns the number of comments associated with a given object by actual registered users.
     * This is intended for outside usage to render stuff like "15 comments". The count is
     * executed unchecked.
     *
     * @return int Number of comments matching a given result.
     */
    public static function count_by_objectguid_filter_anonymous($guid, $status = false)
    {
        $qb = self::_prepare_query($guid, $status);
        $qb->add_constraint('author', '<>', '');
        $qb->add_constraint('content', '<>', '');
        return $qb->count_unchecked();
    }

    private static function _prepare_query($guid, $status = false, $paging = false)
    {
        if ($paging !== false) {
            $qb = new org_openpsa_qbpager('net_nehmer_comments_comment', 'net_nehmer_comments_comment');
            $qb->results_per_page = $paging;
        } else {
            $qb = net_nehmer_comments_comment::new_query_builder();
        }

        if (!is_array($status)) {
            $status = net_nehmer_comments_comment::get_default_status();
        }

        $qb->add_constraint('status', 'IN', $status);
        $qb->add_constraint('objectguid', '=', $guid);

        return $qb;
    }

    /**
     * Check the post against possible spam filters.
     *
     * This will update post status on the background and log the information.
     */
    public function check_spam($config)
    {
        if (!$config->get('enable_spam_check')) {
            // Spam checker is not enabled, skip check
            return;
        }

        $ret = net_nehmer_comments_spamchecker::check_linksleeve($this->title . ' ' . $this->content . ' ' . $this->author);

        if ($ret == net_nehmer_comments_spamchecker::HAM) {
            // Quality content
            debug_add("Linksleeve noted comment \"{$this->title}\" ({$this->guid}) as ham");

            $this->status = net_nehmer_comments_comment::MODERATED;
            $this->update();
            $this->_log_moderation('reported_not_junk', 'linksleeve');
        } elseif ($ret == net_nehmer_comments_spamchecker::SPAM) {
            // Spam
            debug_add("Linksleeve noted comment \"{$this->title}\" ({$this->guid}) as spam");

            $this->status = net_nehmer_comments_comment::JUNK;
            $this->update();
            $this->_log_moderation('confirmed_junk', 'linksleeve');
        }
    }

    public function report_abuse()
    {
        if ($this->status == net_nehmer_comments_comment::MODERATED) {
            return false;
        }

        // Set the status
        if (   $this->can_do('net.nehmer.comments:moderation')
            && !$this->_sudo_requested) {
            $this->status = net_nehmer_comments_comment::ABUSE;
        } else {
            $this->status = net_nehmer_comments_comment::REPORTED_ABUSE;
        }

        if ($this->update()) {
            // Log who reported it
            $this->_log_moderation('reported_abuse');
            return true;
        }
        return false;
    }

    /**
     * Marks the message as confirmed abuse
     */
    public function confirm_abuse()
    {
        if ($this->status == net_nehmer_comments_comment::MODERATED) {
            return false;
        }
        // Set the status
        if (   !$this->can_do('net.nehmer.comments:moderation')
            || $this->_sudo_requested) {
            return false;
        }

        $this->status = net_nehmer_comments_comment::ABUSE;
        if ($this->update()) {
            // Log who reported it
            $this->_log_moderation('confirmed_abuse');
            return true;
        }
        return false;
    }

    /**
     * Marks the message as confirmed junk (spam)
     */
    public function confirm_junk()
    {
        if ($this->status == net_nehmer_comments_comment::MODERATED) {
            return false;
        }

        // Set the status
        if (   !$this->can_do('net.nehmer.comments:moderation')
            || $this->_sudo_requested) {
            return false;
        }

        $this->status = net_nehmer_comments_comment::JUNK;
        if ($this->update()) {
            // Log who reported it
            $this->_log_moderation('confirmed_junk');
            return true;
        }
        return false;
    }

    /**
     * Marks the message as not abuse
     */
    public function report_not_abuse()
    {
        if (   !$this->can_do('net.nehmer.comments:moderation')
            || $this->_sudo_requested) {
            return false;
        }

        // Set the status
        $this->status = net_nehmer_comments_comment::MODERATED;

        if ($this->update()) {
            // Log who reported it
            $this->_log_moderation('reported_not_abuse');
            return true;
        }
        return false;
    }

    function get_logs()
    {
        $log_entries = array();
        $logs = $this->list_parameters('net.nehmer.comments:moderation_log');
        foreach ($logs as $action => $details) {
            // TODO: Show everything only to moderators
            $log_action  = explode(':', $action);
            $log_details = explode(':', $details);

            if (count($log_action) == 2) {
                switch ($log_details[0]) {
                    case 'anonymous':
                        $reporter = 'anonymous';
                        break;
                    case 'linksleeve':
                        $reporter = 'linksleeve';
                        break;
                    default:
                        $user = midcom::get()->auth->get_user($log_details[0]);
                        $reporter = $user->name;
                        break;
                }

                $log_entries[$log_action[1]] = array
                (
                    'action'   => $log_action[0],
                    'reporter' => $reporter,
                    'ip'       => $log_details[1],
                    'browser'  => $log_details[2],
                );
            }
        }
        return $log_entries;
    }

    private function _log_moderation($action = 'marked_spam', $reporter = null, $extra = null)
    {
        if ($reporter === null) {
            if (midcom::get()->auth->user) {
                $reporter = midcom::get()->auth->user->guid;
            } else {
                $reporter = 'anonymous';
            }
        }
        $browser = str_replace(':', '_', $_SERVER['HTTP_USER_AGENT']);
        $date_string = gmdate('Ymd\This');

        $log_action = array
        (
            0 => $action,
            1 => $date_string
        );

        $log_details = array
        (
            0 => $reporter,
            1 => str_replace(':', '_', $_SERVER['REMOTE_ADDR']),
            2 => $browser
        );

        if ($extra !== null) {
            $log_details[] = $extra;
        }

        $this->set_parameter('net.nehmer.comments:moderation_log', implode(':', $log_action), implode(':', $log_details));
    }

    public static function get_default_status()
    {
        $view_status = array
        (
            net_nehmer_comments_comment::NEW_ANONYMOUS,
            net_nehmer_comments_comment::NEW_USER,
            net_nehmer_comments_comment::MODERATED,
        );

        $config = midcom_baseclasses_components_configuration::get('net.nehmer.comments', 'config');
        if ($config->get('show_reported_abuse_as_normal')) {
            $view_status[] = net_nehmer_comments_comment::REPORTED_ABUSE;
        }

        return $view_status;
    }

    /**
     * Update possible ratings cache as requested in configuration
     */
    private function _cache_ratings()
    {
        $config = midcom_baseclasses_components_configuration::get('net.nehmer.comments', 'config');

        if (   $config->get('ratings_enable')
            && (    $config->get('ratings_cache_to_object')
                 || $config->get('comment_count_cache_to_object'))) {
            // Handle ratings
            $comments = net_nehmer_comments_comment::list_by_objectguid($this->objectguid);
            $ratings_total = 0;
            $rating_comments = 0;
            $value = 0;
            foreach ($comments as $comment) {
                if (!empty($comment->rating)) {
                    $rating_comments++;
                    $ratings_total += $comment->rating;
                }
            }

            // Get parent object
            $parent_property = $config->get('ratings_cache_to_object_property');
            midcom::get()->auth->request_sudo('net.nehmer.comments');
            $parent_object = midcom::get()->dbfactory->get_object_by_guid($this->objectguid);

            if ($config->get('ratings_cache_total')) {
                $value = $ratings_total;
            } elseif ($rating_comments != 0) {
                $value = $ratings_total / $rating_comments;
            }

            if ($config->get('ratings_cache_to_object_property_metadata')) {
                $parent_object->metadata->set($parent_property, round($value));
            } else {
                $orig_rcs = $parent_object->_use_rcs;
                $parent_object->_use_rcs = (boolean) $config->get('ratings_cache_to_object_use_rcs');
                // TODO: Figure out whether to round
                $parent_object->$parent_property = $value;
                $parent_object->update();
                $parent_object->_use_rcs = $orig_rcs;
            }

            // Get parent object
            $parent_property = $config->get('comment_count_cache_to_object_property');
            if ($config->get('comment_count_cache_to_object_property_metadata')) {
                $parent_object->metadata->set($parent_property, count($comments));
            } else {
                $orig_rcs = $parent_object->_use_rcs;
                $parent_object->_use_rcs = (boolean) $config->get('comment_count_cache_to_object_use_rcs');
                $parent_object->$parent_property = count($comments);
                $parent_object->update();
                $parent_object->_use_rcs = $orig_rcs;
            }
            midcom::get()->auth->drop_sudo();
        }
    }

    private function _send_notifications()
    {
        //Get the parent object
        try {
            $parent = midcom::get()->dbfactory->get_object_by_guid($this->objectguid);
        } catch (midcom_error $e) {
            $e->log();
            return false;
        }

        if (   empty($this->title)
            && empty($this->content)) {
            // No need to send notifications about empty rating entries
            return false;
        }

        // Construct the message
        $message = $this->_construct_message();

        $authors = explode('|', substr($parent->metadata->authors, 1, -1));
        if (empty($authors)) {
            // Fall back to original creator if authors are not set for some reason
            $authors = array($parent->metadata->creator);
        }

        //Go through all the authors
        foreach ($authors as $author) {
            // Send the notification to each author of the original document
            org_openpsa_notifications::notify('net.nehmer.comments:comment_posted', $author, $message);
        }

        //Get all the subscriptions
        $subscriptions = $parent->list_parameters('net.nehmer.comments:subscription');
        if (!empty($subscriptions)) {
            //Go through each subscription
            foreach (array_keys($subscriptions) as $user_guid) {
                // Send notice
                org_openpsa_notifications::notify('net.nehmer.comments:subscription', $user_guid, $message);
            }
        }
    }

    /**
     * Construct the message
     */
    private function _construct_message()
    {
        // Construct the message
        $message = array();

        // Resolve parent title
        $parent_object = midcom::get()->dbfactory->get_object_by_guid($this->objectguid);
        $ref = midcom_helper_reflector::get($parent_object);
        $parent_title = $ref->get_object_label($parent_object);

        // Resolve commenting user
        $auth = midcom::get()->auth;
        if ($auth->user) {
            $user_string = "{$auth->user->name} ({$auth->user->username})";
        } else {
            $user_string = "{$this->author} (" . midcom::get()->i18n->get_string('anonymous', 'midcom') . ")";
        }

        $message['title'] = sprintf(midcom::get()->i18n->get_string('page %s has been commented by %s', 'net.nehmer.comments'), $parent_title, $user_string);

        $message['content']  = "{$this->title}\n";
        $message['content'] .= "{$this->content}\n\n";
        $message['content'] .= midcom::get()->i18n->get_string('link to page', 'net.nemein.wiki') . ":\n";
        $message['content'] .= midcom::get()->permalinks->create_permalink($this->objectguid);

        $message['abstract'] = $message['title'];

        return $message;
    }

    public function _on_updated()
    {
        $this->_cache_ratings();

        if ($this->_send_notification) {
            //Notify authors and subscribers about the new comment
            $this->_send_notifications();
        }
    }
}
