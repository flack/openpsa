<?php
/**
 * @package org.openpsa.httplib
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\DomCrawler\Crawler;

/**
 * Helpers for HTTP content fetching and handling
 *
 * @package org.openpsa.httplib
 */
class org_openpsa_httplib_helpers extends midcom_baseclasses_components_purecode
{
    private static $_quotes = '"\'';

    /**
     * Get value of a meta tag in HTML page.
     *
     * @param string $html HTML to parse
     * @param string $name Name of the meta tag to fetch
     * @return string Content of the meta tag
     */
    public static function get_meta_value($html, $name)
    {
        $crawler = new Crawler($html);
        return (string) $crawler->filter('head meta[name="' . $name . '"]')->attr('content');
    }

    /**
     * Get value(s) of a link tag(s) in HTML page.
     *
     * @param string $html HTML to parse
     * @param string $relation Relation (rel) or reverse relation (rev) of the link tag to fetch
     * @param string $type Type (type) of the link tag to fetch (defaults to null, meaning all types of the link relation)
     * @return array Links matching given criteria as arrays containing keys title, href and optionally hreflang
     */
    public static function get_link_values($html, $relation, $type = null)
    {
        $crawler = new Crawler($html);
        $nodes = $crawler->filter('head link[rel="' . $relation . '"]');

        if (!is_null($type)) {
            $nodes = $nodes->filter('[type="' . $type . '"]');
        }

        return $nodes->each(function(Crawler $node, $i) {
            $tag = array('title' => false, 'href' => false, 'hreflang' => false);
            foreach ($tag as $property => &$value) {
                if ($node->attr($property) !== null) {
                    $value = $node->attr($property);
                }
            }
            return $tag;
        });
    }

    /**
     * Get value(s) of an anchor tag(s) in HTML page.
     *
     * @param string $html HTML to parse
     * @param string $relation Relation (rel) of the anchor to fetch
     * @return array Links matching given criteria as arrays containing keys title, href and value
     */
    public static function get_anchor_values($html, $relation)
    {
        $crawler = new Crawler($html);
        $nodes = $crawler->filter('a[rel="' . $relation . '"]');

        return $nodes->each(function(Crawler $node, $i) {
            return array(
                'title' => ($node->attr('title') !== null) ? $node->attr('title') : false,
                'href' => ($node->attr('href') !== null) ? $node->attr('href') : false,
                'value' => ($node->text() !== null) ? $node->text() : false,
            );
        });
    }
}
