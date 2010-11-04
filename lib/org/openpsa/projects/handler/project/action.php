<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: action.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Project action handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_project_action extends midcom_baseclasses_components_handler
{

    function __construct()
    {
        parent::__construct();
    }

    function _on_initialize()
    {
    }

    function _load_project($identifier)
    {
        $project = new org_openpsa_projects_project($identifier);

        if (!is_object($project))
        {
            return false;
        }

        return $project;
    }

    /**
     * @todo This should be properly reorganized to its own handlers
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_action($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_request_data['project'] = $this->_load_project($args[0]);
        if (!$this->_request_data['project'])
        {
            return false;
        }

        // Check if the action is a valid one
        $this->_request_data['project_action'] = $args[1];
        switch ($args[1])
        {
            case 'subscribe':
                // If person is already a member just redirect
                $this->_request_data['project']->get_members();
                if (   array_key_exists($_MIDGARD['user'], $this->_request_data['project']->resources)
                    || array_key_exists($_MIDGARD['user'], $this->_request_data['project']->contacts))
                {
                    $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                        . "project/{$this->_request_data['project']->guid}/");
                    // This will exit
                }

                if (!$_MIDCOM->auth->can_do('midgard:create', $this->_request_data['project']))
                {
                    // We usually want to skip ACL here and allow anybody to subscribe
                    $_MIDCOM->auth->request_sudo();
                }
                $_MIDCOM->auth->require_do('midgard:create', $this->_request_data['project']);

                // FIXME: Move this to a method in the project class
                $subscriber = new org_openpsa_projects_task_resource_dba();
                $subscriber->person = $_MIDGARD['user'];
                $subscriber->task = $this->_request_data['project']->id;
                $subscriber->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_PROJECTCONTACT;

                if ($subscriber->create())
                {
                    $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                        . "project/{$this->_request_data['project']->guid}/");
                    // This will exit
                }
                else
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to subscribe, reason " . midcom_application::get_error_string());
                    // This will exit
                }

            case 'unsubscribe':
                // If person is not a subscriber just redirect
                if (!array_key_exists($_MIDGARD['user'], $this->_request_data['project']->contacts))
                {
                    $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                        . "project/{$this->_request_data['project']->guid}/");
                    // This will exit
                }

                $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
                $qb->add_constraint('person', '=', $_MIDGARD['user']);
                $qb->add_constraint('task', '=', $this->_request_data['project']->id);
                $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTCONTACT);
                $ret = $qb->execute();
                if (   is_array($ret)
                    && count($ret) > 0)
                {
                    foreach ($ret as $subscriber)
                    {
                        $subscriber->delete();
                    }
                }
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . "project/{$this->_request_data['project']->guid}/");
                // This will exit

            case 'create_news':
                $_MIDCOM->auth->require_do('midgard:update', $this->_request_data['project']);
                $_MIDCOM->auth->require_do('midgard:create', $this->_request_data['project_topic']);

                if ($this->_request_data['project']->newsTopic)
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "The news topic already exists");
                    // This will exit
                }

                if (!$this->_request_data['config']->get('enable_project_news'))
                {
                    return false;
                }

                // Create the news topic
                // FIXME: Move this to a method in the project class
                $news_topic = new midcom_baseclasses_database_topic();
                $news_topic->up = $this->_request_data['project_topic']->id;
                $news_topic->extra = sprintf($this->_l10n->get("%s news area"), $this->_request_data['project']->title);
                $news_topic->component = 'net.nehmer.blog';
                $news_topic->name = midcom_generate_urlname_from_string($news_topic->extra);
                $news_topic->create();

                if ($news_topic->id)
                {
                    // Set the topic to use correct component
                    $news_topic = new midcom_baseclasses_database_topic($news_topic->id);

                    // Fix the ACLs for the topic
                    $sync = new org_openpsa_core_acl_synchronizer();
                    $sync->write_acls($news_topic, $this->_request_data['project']->orgOpenpsaOwnerWg, $this->_request_data['project']->orgOpenpsaAccesstype);

                    // Add the news topic to the project
                    $this->_request_data['project']->newsTopic = $news_topic->id;
                    $this->_request_data['project']->update();

                    $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                        . "project/" . $this->_request_data["project"]->guid);
                    // This will exit
                }
                else
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create project news topic, reason " . $news_topic->errstr);
                    // This will exit
                }

            case 'create_forum':
                $_MIDCOM->auth->require_do('midgard:update', $this->_request_data['project']);
                $_MIDCOM->auth->require_do('midgard:create', $this->_request_data['project_topic']);

                if (!$this->_request_data['config']->get('enable_project_forum'))
                {
                    return false;
                }

                if ($this->_request_data['project']->forumTopic)
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "The forum topic already exists");
                    // This will exit
                }

                // Create the news topic
                // FIXME: Move this to a method in the project class
                $forum_topic = new midcom_baseclasses_database_topic();
                $forum_topic->up = $this->_request_data['project_topic']->id;
                $forum_topic->extra = sprintf($this->_l10n->get("%s discussion"), $this->_request_data['project']->title);
                $forum_topic->component = 'net.nemein.discussion';
                $forum_topic->name = midcom_generate_urlname_from_string($forum_topic->extra);
                $forum_topic->create();

                if ($forum_topic->id)
                {
                    // Set the topic to use correct component
                    $forum_topic = new midcom_baseclasses_database_topic($forum_topic->id);

                    // Fix the ACLs for the topic
                    $sync = new org_openpsa_core_acl_synchronizer();
                    $sync->write_acls($forum_topic, $this->_request_data['project']->orgOpenpsaOwnerWg, $this->_request_data['project']->orgOpenpsaAccesstype);

                    // Add the news topic to the project
                    $this->_request_data['project']->forumTopic = $forum_topic->id;
                    $this->_request_data['project']->update();

                    $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                        . "project/" . $this->_request_data["project"]->guid);
                    // This will exit
                }
                else
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create project forum topic, reason " . $forum_topic->errstr);
                    // This will exit
                }

            default:
                return false;
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_action($handler_id, &$data)
    {
    }
    
}
?>