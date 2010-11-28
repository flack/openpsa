#!/usr/bin/php
<?php
error_reporting(E_ALL);
require_once('midcom/lib/org/openpsa/mail/nonmidcom.php');
$parser = new org_openpsa_mail();
while(!feof(STDIN))
{
    $parser->body .= fread(STDIN, 1024);
}
fclose(STDIN);
$parser->mime_decode();
print_r($parser);
?>