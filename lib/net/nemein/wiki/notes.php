<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wiki note helper class to be used by other components
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_notes extends midcom_baseclasses_components_purecode
{
    var $target = null;
    var $target_node = null;
    var $wiki = null;
    var $related = array();
    private $_related_guids = array();
    var $link_target = 'wiki';
    var $new_wikipage = null;
    private $_paged_qb = null;

    public function __construct($target_node, $target_object, $new_wikipage = null)
    {
        parent::__construct();

        $this->target_node = $target_node;
        $this->target = midcom::get('dbfactory')->get_object_by_guid($target_object);
        $this->wiki = midcom_helper_misc::find_node_by_component('net.nemein.wiki');

        if ($new_wikipage)
        {
            $this->new_wikipage = rawurlencode(str_replace('/', '-', $new_wikipage));
        }
    }

    private function _list_related_guids_of_a_person($person)
    {
        // We're in person, so we need to also look events he/she participates to
        $mc = midcom_db_eventmember::new_collector('uid', $person->id);
        $memberships = $mc->get_values('eid');

        if (!empty($memberships))
        {
            $mc = midcom_db_event::new_collector('metadata.deleted', false);
            $mc->add_constraint('id', 'IN', $memberships);
            $mc->execute();
            $guids = $mc->list_keys();
            foreach (array_keys($guids) as $guid)
            {
                $this->_related_guids[$guid] = true;
            }
        }

        // And also list events directly connected to them
        $this->_related_guids[$person->guid] = true;
    }

    private function _list_related()
    {
        if (!$this->wiki)
        {
            return false;
        }

        // The depth of relation looked depends on object type
        if (is_subclass_of($this->target, 'midgard_person'))
        {
            $this->_list_related_guids_of_a_person($this->target);
        }
        else if (is_subclass_of($this->target, 'midgard_group'))
        {
            // Include notes about members of the group
            $mc = midcom_db_member::new_collector('gid', $this->target->id);
            $members = $mc->get_values('uid');

            if (!empty($members))
            {
                $qb = midcom_db_person::new_query_builder();
                $qb->add_constraint('id', 'IN', $members);
                $persons = $qb->execute();
                foreach ($persons as $person)
                {
                    $this->_list_related_guids_of_a_person($person);
                }
            }

            // And the group itself
            $this->_related_guids[$this->target->guid] = true;
        }
        else
        {
            $this->_related_guids[$this->target->guid] = true;
        }

        if (count($this->_related_guids) > 0)
        {
            if (class_exists('org_openpsa_qbpager_direct'))
            {
                $qb = new org_openpsa_qbpager_direct('midgard_parameter', 'related_notes');
                $qb->results_per_page = 10;
                $this->_paged_qb = $qb;
            }
            else
            {
                $qb = new midgard_query_builder('midgard_parameter');
            }

            $qb->add_constraint('name', 'IN', array_keys($this->_related_guids));

            $qb->add_constraint('domain', '=', 'net.nemein.wiki:related_to');
            $ret = $qb->execute();
            if (!empty($ret))
            {
                foreach ($ret as $related_to)
                {
                    try
                    {
                        $wikipage = new net_nemein_wiki_wikipage($related_to->parentguid);
                        $this->related[$wikipage->guid] = $wikipage;
                    }
                    catch (midcom_error $e)
                    {
                        $e->log();
                    }
                }
            }
        }
    }

    function populate_toolbar(midcom_helper_toolbar $toolbar)
    {
        $enable_creation = false;
        if (   $this->wiki[MIDCOM_NAV_OBJECT]->can_do('midgard:create')
            && $this->new_wikipage)
        {
            // Check for duplicates
            $qb = net_nemein_wiki_wikipage::new_query_builder();
            $qb->add_constraint('topic', '=', $this->wiki[MIDCOM_NAV_OBJECT]->id);
            $qb->add_constraint('title', '=', rawurldecode($this->new_wikipage));
            $enable_creation = ($qb->count() == 0);
        }

       $toolbar->add_item
       (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$this->wiki[MIDCOM_NAV_ABSOLUTEURL]}create/{$this->new_wikipage}/{$this->target_node[MIDCOM_NAV_GUID]}/{$this->target->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create note'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                MIDCOM_TOOLBAR_ENABLED => $enable_creation,
                MIDCOM_TOOLBAR_OPTIONS => array
                (
                    'target' => 'wiki',
                ),
            )
        );
    }

    function show_related()
    {
        if (!$this->wiki)
        {
            return false;
        }

        $this->_list_related();
        if (count($this->related) > 0)
        {
            echo "<div class=\"area net_nemein_wiki related\">\n";

            echo "<h2>".$this->_l10n->get('related notes')."</h2>\n";

            echo "<ul class=\"related\">\n";
            if ($this->_paged_qb)
            {
                $this->_paged_qb->show_pages();
            }
            foreach ($this->related as $wikipage)
            {
                echo "<li><a rel=\"note\" target=\"{$this->link_target}\" href=\"{$this->wiki[MIDCOM_NAV_ABSOLUTEURL]}{$wikipage->name}/\">{$wikipage->title}</a></li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
    }
}
?>