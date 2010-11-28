<?php
/**
 * @package net.nehmer.markdown
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 17358 2008-09-03 12:21:13Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Markdown library, based on lib_markdown.
 *
 * Original Markdown code is under a BSD-style license, as described on
 * http://www.michelf.com/projects/php-markdown/license/
 *
 * Copyright (c) 2004 John Gruber http://daringfireball.net/projects/markdown/
 *
 * Copyright (c) 2004 Michel Fortin - PHP Port http://www.michelf.com/projects/php-markdown/
 *
 * The library uses http://www.michelf.com/projects/php-markdown/extra/ extended
 * Markdown Syntax.
 *
 * Be aware, that the whole Markdown system is procedural with a bunch of global variables.
 * I take no responsibilities for the quality of that piece of code. In my eyes it could need
 * some decent refactoring.
 *
 * To allow for easier extension at later times, a wrapper class (net_nehmer_markdown_markdown.php)
 * has already been created.
 *
 * @package net.nehmer.markdown
 */
class net_nehmer_markdown_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        $this->_autoload_files = array
        (
            'lib/markdown.php'
        );
    }
}
?>