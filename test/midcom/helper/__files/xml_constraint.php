<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Constraint for comparing XML strings produced by objectmapper. It removes
 * the order, which doesn't seem to be stable between different installations
 *
 * @package openpsa.test
 */
class xml_comparison extends Constraint
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $this->_normalize_string($value, 2);
    }

    private function _normalize_string($string, int $argument = 1)
    {
        $doc = new DOMDocument;

        if (!$doc->loadXML($string)) {
            throw new InvalidArgumentException('$string is not valid XML');
        }

        $xpath = new DOMXPath($doc);
        $rootnode = $xpath->query('//*')->item(0)->cloneNode();
        $new_doc = new DOMDocument;
        $new_doc->preserveWhiteSpace = true;
        $rootnode = $new_doc->importNode($rootnode);
        $new_doc->appendChild($rootnode);
        $nodes = $xpath->query('//' . $rootnode->tagName . '/*');
        $this->copy($nodes, $new_doc);

        $metanode = $new_doc->getElementsByTagName('midcom_helper_metadata')->item(0);
        if ($metanode) {
            $metafields = $xpath->query('//*/midcom_helper_metadata/*');
            $this->copy($metafields, $new_doc, $metanode);
        }
        $new_doc->formatOutput = true;

        return $new_doc->saveXML();
    }

    private function copy($nodes, DOMDocument $new_doc, ?DOMNode $parent = null)
    {
        $map = [];
        foreach ($nodes as $node) {
            $map[$node->tagName] = $node;
        }
        ksort($map);
        foreach ($map as $node) {
            $node = $new_doc->importNode($node, $node->tagName !== 'midcom_helper_metadata');
            if ($parent) {
                $parent->appendChild($node);
            } else {
                $new_doc->documentElement->appendChild($node);
            }
        }
    }

    public function matches($other) : bool
    {
        return $this->value == $other;
    }

    public function evaluate($other, $description = '', $returnResult = false) : ?bool
    {
        if (!is_string($other)) {
            throw new InvalidArgumentException('$other must be string');
        }

        return parent::evaluate($this->_normalize_string($other), $description, $returnResult);
    }

    public function toString() : string
    {
        return 'is equal to ' . (new \SebastianBergmann\Exporter\Exporter)->export($this->value);
    }
}
