<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Duplicates handler
 *
 * @todo This cannot work in 8.09, since metadata fields like creator are read-only.
 * Also, deleting persons isn't supported (although it works if you just call delete())
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_duplicates_person extends midcom_baseclasses_components_handler
{
    private $notfound = false;

    public function _handler_sidebyside(Request $request, array &$data)
    {
        $data['person1'] = false;
        $data['person2'] = false;
        $data['loop_i'] = 0;

        if ($request->request->has('org_openpsa_contacts_handler_duplicates_person_loop_i')) {
            $data['loop_i'] = $request->request->getInt('org_openpsa_contacts_handler_duplicates_person_loop_i');
            if ($request->request->has('org_openpsa_contacts_handler_duplicates_person_decide_later')) {
                $data['loop_i']++;
            }
        }
        $this->process_submit($request);

        $this->load_next();

        $this->set_active_leaf('persons_merge');
        $this->add_breadcrumb('', $this->_l10n->get('merge persons'));

        if (!$this->notfound) {
            return $this->show('show-duplicate-persons');
        }
        return $this->show('show-duplicate-persons-notfound');
    }

    private function load_next()
    {
        $i =& $this->_request_data['loop_i'];
        while ($i < 100) {
            debug_add("Loop iteration {$i}");
            $qb = new midgard_query_builder('midgard_parameter');
            $qb->add_constraint('domain', '=', 'org.openpsa.contacts.duplicates:possible_duplicate');
            $qb->add_order('name', 'ASC');
            $qb->set_limit(1);
            if ($i > 0) {
                $qb->set_offset($i);
            }
            $ret = $qb->execute();

            if (empty($ret)) {
                debug_add("No more results to be had, setting notfound and breaking out of loop");
                $this->notfound = true;
                break;
            }

            $param = $ret[0];
            debug_add("Found duplicate mark on person {$param->parentguid} for person {$param->name}");
            try {
                $person1 = new org_openpsa_contacts_person_dba($param->parentguid);
                $person2 = new org_openpsa_contacts_person_dba($param->name);
            } catch (midcom_error $e) {
                $i++;
                continue;
            }
            // Make sure we actually have enough rights to do this
            if (   !$person1->can_do('midgard:update')
                || !$person1->can_do('midgard:delete')
                || !$person2->can_do('midgard:update')
                || !$person2->can_do('midgard:delete')) {
                debug_add("Insufficient rights to merge these two, continuing to see if we have more");
                $i++;
                continue;
            }
            // Extra sanity check (in case of semi-successful not-duplicate mark)
            if (   $person1->get_parameter('org.openpsa.contacts.duplicates:not_duplicate', $person2->guid)
                || $person2->get_parameter('org.openpsa.contacts.duplicates:not_duplicate', $person1->guid)) {
                debug_add("It seems these two (#{$person1->id} and #{$person2->id}) have also marked as not duplicates, some cleanup might be a good thing", MIDCOM_LOG_WARN);
                $i++;
                continue;
            }

            $this->_request_data['person1'] = $person1;
            $this->_request_data['person2'] = $person2;
            break;
        }
    }

    private function process_submit(Request $request)
    {
        $keep = $request->request->get('org_openpsa_contacts_handler_duplicates_person_keep');
        $options = $request->request->get('org_openpsa_contacts_handler_duplicates_person_options');
        if (!empty($keep) && count($options) == 2) {
            $option1 = new org_openpsa_contacts_person_dba($options[1]);
            $option2 = new org_openpsa_contacts_person_dba($options[2]);
            $keep = key($keep);
            if ($keep == 'both') {
                $option1->require_do('midgard:update');
                $option2->require_do('midgard:update');
                if (   $option1->set_parameter('org.openpsa.contacts.duplicates:not_duplicate', $option2->guid, time())
                    && $option2->set_parameter('org.openpsa.contacts.duplicates:not_duplicate', $option1->guid, time())) {
                    // Clear the possible duplicate parameters
                    $option1->delete_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $option2->guid);
                    $option2->delete_parameter('org.openpsa.contacts.duplicates:possible_duplicate', $option1->guid);

                    // TODO: Localize
                    midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.contacts'), "Keeping both \"{$option1->name}\" and \"{$option2->name}\", they will not be marked as duplicates in the future", 'ok');
                } else {
                    $errstr = midcom_connection::get_error_string();
                    // Failed to set as not duplicate, clear parameter that might have been set (could have only been the first)
                    $option1->delete_parameter('org.openpsa.contacts.duplicates:not_duplicate', $option2->guid);

                    // TODO: Localize
                    midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.contacts'), "Failed to mark #{$option1->id} and # {$option2->id} as not duplicates, errstr: {$errstr}", 'error');
                }
            } else {
                if ($keep == $option1->guid) {
                    $person1 =& $option1;
                    $person2 =& $option2;
                } elseif ($keep == $option2->guid) {
                    $person1 =& $option2;
                    $person2 =& $option1;
                } else {
                    throw new midcom_error('Something weird happened (basically we got bogus data)');
                }
                $person1->require_do('midgard:update');
                $person2->require_do('midgard:delete');

                try {
                    $merger = new org_openpsa_contacts_duplicates_merge('person', $this->_config);
                    $merger->merge_delete($person1, $person2);
                } catch (midcom_error $e) {
                    // TODO: Localize
                    midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.contacts'), 'Merge failed, errstr: ' . $e->getMessage(), 'error');
                }
            }

            //PONDER: redirect to avoid reloading the POST in case user presses reload ??
        }
    }
}
