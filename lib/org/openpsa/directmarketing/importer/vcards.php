<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Sabre\VObject\Splitter\VCard;
use Sabre\VObject\Component;

/**
 * CSV Importer for subscribers
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_importer_vcards extends org_openpsa_directmarketing_importer
{
    public function parse($input)
    {
        $reader = new VCard(fopen($input, 'r'));
        $parsed = array();

        while ($card = $reader->getNext())
        {
            $contact = $this->_parse_vcard($card);
            if (count($contact['person']) > 0)
            {
                // We have parsed some contact info.
                $parsed[] = $contact;
            }
        }
        return $parsed;
    }

    private function _parse_vcard(Component $card)
    {
        $contact = array
        (
            'person'              => array(),
            'organization'        => array(),
            'organization_member' => array(),
        );

        if ($card->FN)
        {
            $name_parts = explode(' ', $card->FN->value, 2);
            if (sizeof($name_parts) > 1)
            {
                $contact['person']['lastname'] = $name_parts[1];
                $contact['person']['firstname'] = $name_parts[0];
            }
            else
            {
                $contact['person']['lastname'] = $name_parts[0];
            }
        }
        if ($card->TEL)
        {
            foreach ($card->TEL as $tel)
            {
                switch ($tel['TYPE'])
                {
                    case 'CELL':
                        $contact['person']['handphone'] = $tel->value;
                        break;
                    case 'HOME':
                        $contact['person']['homephone'] = $tel->value;
                        break;
                    case 'WORK':
                        $contact['person']['workphone'] = $tel->value;
                        break;
                }
            }
        }

        if ($card->ORG)
        {
            $contact['organization']['official'] = $card->ORG->value;
        }
        if ($card->TITLE)
        {
            $contact['organization_member']['title'] = $card->TITLE->value;
        }
        if ($card->EMAIL)
        {
            $contact['person']['email'] = $card->EMAIL->value;
        }
        if ($card->{'X-SKYPE-USERNAME'})
        {
            $contact['person']['skype'] = $card->{'X-SKYPE-USERNAME'}->value;
        }
        if ($card->UID)
        {
            $contact['person']['external-uid'] = $card->UID->value;
        }
        elseif ($card->{'X-ABUID'})
        {
            $contact['person']['external-uid'] = $card->{'X-ABUID'}->value;
        }
        return $contact;
    }
}
