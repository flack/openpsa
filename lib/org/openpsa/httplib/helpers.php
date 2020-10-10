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
    /**
     * Get value of a meta tag in HTML page.
     */
    public static function get_meta_value(string $html, string $name) : ?string
    {
        $crawler = new Crawler($html);
        if ($crawler->filter('head meta[name="' . $name . '"]')->count() == 0) {
            return null;
        }
        return (string) $crawler->filter('head meta[name="' . $name . '"]')->attr('content');
    }

    /**
     * Get value(s) of a link tag(s) in HTML page.
     *
     * @param string $html HTML to parse
     * @param string $relation Relation (rel) or reverse relation (rev) of the link tag to fetch
     * @return array Links matching given criteria as arrays containing keys title, href and optionally hreflang
     */
    public static function get_link_values(string $html, string $relation) : array
    {
        $crawler = new Crawler($html);
        $nodes = $crawler->filter('head link[rel="' . $relation . '"]');

        return $nodes->each(function(Crawler $node, $i) {
            $tag = ['title' => false, 'href' => false, 'hreflang' => false];
            foreach ($tag as $property => &$value) {
                if ($node->attr($property) !== null) {
                    $value = $node->attr($property);
                }
            }
            return $tag;
        });
    }
}
