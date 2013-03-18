<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * CSV Importer for subscribers
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_importer_vcards extends org_openpsa_directmarketing_importer
{
    public function parse($input)
    {
        $parsed = array();
        $parser = new Contact_Vcard_Parse();
        $cards = @$parser->fromFile($input);

        if (empty($cards))
        {
            return $parsed;
        }

        foreach ($cards as $card)
        {
            // Empty the person array before going through vCard data
            $contact = array
            (
                'person'              => array(),
                'organization'        => array(),
                'organization_member' => array(),
            );

            // Start parsing
            if (   array_key_exists('N', $card)
                && array_key_exists('value', $card['N'][0])
                && is_array($card['N'][0]['value']))
            {
                // FIXME: We should do something about character encodings
                $contact['person']['lastname'] = $card['N'][0]['value'][0][0];
                $contact['person']['firstname'] = $card['N'][0]['value'][1][0];
            }

            if (array_key_exists('TEL', $card))
            {
                foreach ($card['TEL'] as $number)
                {
                    if (array_key_exists('param', $number))
                    {
                        if (array_key_exists('TYPE', $number['param']))
                        {
                            switch ($number['param']['TYPE'][0])
                            {
                                case 'CELL':
                                    $contact['person']['handphone'] = $number['value'][0][0];
                                    break;
                                case 'HOME':
                                    $contact['person']['homephone'] = $number['value'][0][0];
                                    break;
                                case 'WORK':
                                    $contact['person']['workphone'] = $number['value'][0][0];
                                    break;
                            }
                        }
                    }
                }
            }

            if (array_key_exists('ORG', $card))
            {
                $contact['organization']['official'] = $card['ORG'][0]['value'][0][0];
            }

            if (array_key_exists('TITLE', $card))
            {
                $contact['organization_member']['title'] = $card['TITLE'][0]['value'][0][0];
            }

            if (array_key_exists('EMAIL', $card))
            {
                $contact['person']['email'] = $card['EMAIL'][0]['value'][0][0];
            }

            if (array_key_exists('X-SKYPE-USERNAME', $card))
            {
                $contact['person']['skype'] = $card['X-SKYPE-USERNAME'][0]['value'][0][0];
            }

            if (array_key_exists('UID', $card))
            {
                $contact['person']['external-uid'] = $card['UID'][0]['value'][0][0];
            }
            elseif (array_key_exists('X-ABUID', $card))
            {
                $contact['person']['external-uid'] = $card['X-ABUID'][0]['value'][0][0];
            }

            if (count($contact['person']) > 0)
            {
                // We have parsed some contact info.

                // Convert fields from latin-1 to MidCOM charset (usually utf-8)
                foreach ($contact as $type => $fields)
                {
                    foreach ($fields as $key => $value)
                    {
                        $contact[$type][$key] = iconv('ISO-8859-1', midcom::get('i18n')->get_current_charset(), $value);
                    }
                }

                // TODO: Make sanity checks before adding
                $parsed[] = $contact;
            }
        }

        return $parsed;
    }
}
?>