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
 * @property string $author
 * @property string $objectguid
 * @property string $title
 * @property string $content
 * @property integer $rating
 * @property string $remoteid Remote ID for comments fetched from external source (rss …)
 * @property string $ip
 * @property integer $status
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

    /**
     * DBA magic defaults which assign write privileges for all USERS, so that they can
     * add new comments at will.
     */
    public function get_class_magic_default_privileges()
    {
        return [
            'EVERYONE' => [],
            'ANONYMOUS' => [],
            'USERS' => [
                'midgard:create' => MIDCOM_PRIVILEGE_ALLOW
            ],
        ];
    }

    /**
     * Returns a list of comments applicable to a given object, ordered by creation
     * date.
     *
     * @return net_nehmer_comments_comment[] List of applicable comments.
     */
    public static function list_by_objectguid(string $guid, $limit = false, string $order = 'ASC', $paging = false)
    {
        $qb = self::_prepare_query($guid, $paging, $limit);
        $qb->add_order('metadata.published', $order);

        if ($paging !== false) {
            return $qb;
        }

        return $qb->execute();
    }

    /**
     * Returns a list of comments applicable to a given object
     * not displaying empty comments or anonymous posts,
     * ordered by creation date.
     *
     * @return net_nehmer_comments_comment[] List of applicable comments.
     */
    public static function list_by_objectguid_filter_anonymous(string $guid, $limit = false, string $order = 'ASC', $paging = false)
    {
        $qb = self::_prepare_query($guid, $paging, $limit);
        $qb->add_order('metadata.published', $order);
        $qb->add_constraint('author', '<>', '');
        $qb->add_constraint('content', '<>', '');

        if ($paging !== false) {
            return $qb;
        }

        return $qb->execute();
    }

    /**
     * Returns the number of comments associated with a given object. This is intended for
     * outside usage to render stuff like "15 comments". The count is executed unchecked.
     */
    public static function count_by_objectguid(string $guid) : int
    {
        $qb = self::_prepare_query($guid);
        return $qb->count_unchecked();
    }

    /**
     * Returns the number of comments associated with a given object by actual registered users.
     * This is intended for outside usage to render stuff like "15 comments". The count is
     * executed unchecked.
     */
    public static function count_by_objectguid_filter_anonymous(string $guid) : int
    {
        $qb = self::_prepare_query($guid);
        $qb->add_constraint('author', '<>', '');
        $qb->add_constraint('content', '<>', '');
        return $qb->count_unchecked();
    }

    private static function _prepare_query(string $guid, $paging = false, $limit = false) : midcom_core_querybuilder
    {
        if ($paging !== false) {
            $qb = new org_openpsa_qbpager(self::class, 'net_nehmer_comments_comment');
            $qb->results_per_page = $paging;
        } else {
            $qb = self::new_query_builder();
            if ($limit) {
                $qb->set_limit($limit);
            }
        }

        $qb->add_constraint('status', 'IN', self::get_default_status());
        $qb->add_constraint('objectguid', '=', $guid);

        return $qb;
    }

    public function get_logs() : array
    {
        $log_entries = [];
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

                $log_entries[$log_action[1]] = [
                    'action'   => $log_action[0],
                    'reporter' => $reporter,
                    'ip'       => $log_details[1],
                    'browser'  => $log_details[2],
                ];
            }
        }
        return $log_entries;
    }

    public function moderate(int $status, string $action, $reporter = null) : bool
    {
        $this->status = $status;
        if (!$this->update()) {
            return false;
        }
        // Log who reported it
        if ($reporter === null) {
            $reporter = midcom::get()->auth->user->guid ?? 'anonymous';
        }
        $browser = str_replace(':', '_', $_SERVER['HTTP_USER_AGENT']);
        $address = str_replace(':', '_', $_SERVER['REMOTE_ADDR']);

        $log_action = [$action, gmdate('Ymd\This')];
        $log_details = [$reporter, $address, $browser];

        $this->set_parameter('net.nehmer.comments:moderation_log', implode(':', $log_action), implode(':', $log_details));
        return true;
    }

    public static function get_default_status() : array
    {
        $view_status = [
            self::NEW_ANONYMOUS,
            self::NEW_USER,
            self::MODERATED,
        ];

        $config = midcom_baseclasses_components_configuration::get('net.nehmer.comments', 'config');
        if ($config->get('show_reported_abuse_as_normal')) {
            $view_status[] = self::REPORTED_ABUSE;
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
            $comments = self::list_by_objectguid($this->objectguid);
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

            $parent_property = $config->get('comment_count_cache_to_object_property');
            $orig_rcs = $parent_object->_use_rcs;
            $parent_object->_use_rcs = (boolean) $config->get('comment_count_cache_to_object_use_rcs');
            $parent_object->$parent_property = count($comments);
            $parent_object->update();
            $parent_object->_use_rcs = $orig_rcs;

            midcom::get()->auth->drop_sudo();
        }
    }

    private function _send_notifications()
    {
        if (   empty($this->title)
            && empty($this->content)) {
            // No need to send notifications about empty rating entries
            return;
        }
        //Get the parent object
        try {
            $parent = midcom::get()->dbfactory->get_object_by_guid($this->objectguid);
        } catch (midcom_error $e) {
            $e->log();
            return;
        }

        // Construct the message
        $message = $this->_construct_message();

        $authors = explode('|', substr($parent->metadata->authors, 1, -1));
        if (empty($authors)) {
            // Fall back to original creator if authors are not set for some reason
            $authors = [$parent->metadata->creator];
        }

        //Go through all the authors
        foreach ($authors as $author) {
            // Send the notification to each author of the original document
            org_openpsa_notifications::notify('net.nehmer.comments:comment_posted', $author, $message);
        }

        $subscriptions = $parent->list_parameters('net.nehmer.comments:subscription');
        //Go through each subscription
        foreach (array_keys($subscriptions) as $user_guid) {
            // Send notice
            org_openpsa_notifications::notify('net.nehmer.comments:subscription', $user_guid, $message);
        }
    }

    /**
     * Construct the message
     */
    private function _construct_message() : array
    {
        // Construct the message
        $message = [];

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
