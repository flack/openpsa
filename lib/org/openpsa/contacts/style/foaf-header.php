<?php
midcom::get()->header("Content-type: text/xml; charset=UTF-8");
echo "<rdf:RDF\n";
echo "xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n";
echo "xmlns:foaf=\"http://xmlns.com/foaf/0.1/\"\n";
// For now GUIDs are set to the Exorcist Midgard namespace
echo "xmlns:mgd=\"http://ns.yukatan.fi/2005/midgard\">\n";
