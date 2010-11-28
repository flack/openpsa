<?php
/**
 * @package org.openpsa.httplib
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * hreview profile for hkit
 *  tom morris - 23 oct 2006
 */
    $this->root_class = 'hreview';

    $this->classes = array(
    'item',
    'type',
    'reviewer',
    'version',

    'summary',
    'dtreviewed',
    'description',
    'url',
    'photo',
    'fn',
    array('rating', 'value', 'best')
    );

    // classes that must only appear once per event
    $this->singles = array(
        'item'
    );

    // classes that are required (not strictly enforced - give at least one!)
    $this->required = array(
        'item'
    );

    $this->att_map = array(
        'url'    => array('A|href', 'IMG|src', 'AREA|href')
    );


    $this->callbacks = array(
        'url'    => array($this, 'resolvePath'),
    );



    function hKit_hreview_post($a)
    {
        foreach ($a as &$hreview){
            hKit_labels_toUpper($hreview);
        }

        return $a;
    }


    function hKit_labels_toUpper(&$hreview)
    {
        $hreview = array_change_key_case($hreview, CASE_UPPER);
    }
?>