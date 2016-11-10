<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Constraint for comparing XML strings produced by objectmapper. It removes
 * differences between Midgard 1 and Midgard 2, f.x. removed properties, and
 * the order, which doesn't seem to be stable between different installations
 *
 * @package openpsa.test
 */
class xml_comparison extends PHPUnit_Framework_Constraint_IsEqual
{
    public function __construct($value, $delta = 0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        if (!is_string($value)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }
        $this->value = $this->_normalize_string($value, 2);
    }

    private function _normalize_string($string, $argument = 1)
    {
        $doc = new DOMDocument;

        if (!$doc->loadXML($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory($argument, 'valid XML');
        }
        $xpath = new DOMXPath($doc);
        $rootnode = $xpath->query('//*')->item(0)->cloneNode();
        $new_doc = new DOMDocument;
        $new_doc->preserveWhiteSpace = true;
        $rootnode = $new_doc->importNode($rootnode);
        $new_doc->appendChild($rootnode);

        $nodes = $xpath->query('//*/*');
        $map = array();
        foreach ($nodes as $node) {
            if (   $node->tagName == 'lang'
                || $node->tagName == 'sid'
                || $node->tagName == 'sitegroup') {
                //skip midgard 1 properties
                continue;
            }
            $map[$node->tagName] = $node;
        }
        ksort($map);
        foreach ($map as $node) {
            $node = $new_doc->importNode($node, true);
            $new_doc->documentElement->appendChild($node);
        }
        $new_doc->formatOutput = true;
        return $new_doc->saveXML();
    }

    public function evaluate($other, $description = '', $returnResult = false)
    {
        if (!is_string($other)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        return parent::evaluate($this->_normalize_string($other));
    }

    public function toString()
    {
        return 'XML outputs match';
    }
}
