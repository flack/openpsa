<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class provides methods to make XML for the different solr xml requests.
 *
 * @package midcom.services
 * @see midcom_services_indexer
 */
class midcom_services_indexer_solrDocumentFactory
{
    /**
     * The xml document to post.
     *
     * @var DOMDocument
     */
    private $xml;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->xml = new DOMDocument('1.0', 'UTF-8');
    }

    /**
     * Adds a document to the index.
     */
    public function add(midcom_services_indexer_document $document)
    {
        if (empty($this->xml->documentElement)) {
            $root = $this->xml->createElement('add');
            $this->xml->appendChild($root);
        }
        $element = $this->xml->createElement('doc');
        $this->xml->documentElement->appendChild($element);
        $field = $this->xml->createElement('field');
        $field->setAttribute('name', 'RI');
        $field->nodeValue = $document->RI;
        $element->appendChild($field);

        foreach ($document->list_fields() as $field_name) {
            $field_record = $document->get_field_record($field_name);
            $field = $this->xml->createElement('field');
            $field->setAttribute('name', $field_record['name']);
            // remove control characters (except \n)
            $value = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/', '', $field_record['content']);
            $field->appendChild($this->xml->createCDATASection($value));
            $element->appendChild($field);
        }
    }

    /**
     * Deletes one element
     */
    public function delete(string $id)
    {
        $root = $this->xml->createElement('delete');
        $this->xml->appendChild($root);
        $query = $this->xml->createElement('query');
        $this->xml->documentElement->appendChild($query);
        $query->nodeValue = 'RI:' . $id . '*';
    }

    /**
     * Deletes all elements with the id defined
     * (this should be all midgard documents)
     */
    public function delete_all(string $constraint)
    {
        $this->reset();
        $root = $this->xml->createElement('delete');
        $this->xml->appendChild($root);
        $query = $this->xml->createElement('query');
        $this->xml->documentElement->appendChild($query);
        $query->nodeValue = "RI:[ * TO * ]";
        if (!empty($constraint)) {
            $query->nodeValue .= ' AND ' . $constraint;
        }
    }

    /**
     * Returns the generated XML
     */
    public function to_xml() : string
    {
        return $this->xml->saveXML();
    }
}
