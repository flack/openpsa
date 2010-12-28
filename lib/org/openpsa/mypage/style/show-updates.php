<?php
$view_types = array
(
    'today',
    'yesterday',
);

foreach ($view_types as $type)
{
    if ($data[$type])
    {
        echo "<div class=\"area\">\n";
        echo "<h2>" . sprintf($data['l10n']->get("updated %s"), $type) . "</h2>\n";
        echo "<ul class=\"updated\">\n";
        foreach ($data[$type] as $document)
        {
            $class = explode('.', $document->_fields['__COMPONENT']['content']);
            $class = $class[count($class)-1];

            $onclick = '';
            switch ($class)
            {
                case "calendar":
                    $url = "#";
                    $onclick = " onclick=\"javascript:window.open('{$document->document_url}', 'event', 'toolbar=0,location=0,status=0,height=600,width=300,resizable=1');\"";
                    break;
                default:
                    $url = $document->document_url;
                    break;
            }

            try
            {
                if ($document->_fields['__EDITOR']['content'])
                {
                    $editor = new midcom_db_person($document->_fields['__EDITOR']['content']);
                }
                else
                {
                    $editor = new midcom_db_person($document->_fields['__CREATOR']['content']);
                }
                $contact = new org_openpsa_contactwidget($editor);
                echo "<li class=\"updated-{$class}\"><a href=\"{$url}\"{$onclick}>{$document->title}</a> <div class=\"metadata\">" . strftime("%x %X", $document->_fields['__EDITED_TS']['content']) . " (" . $contact->show_inline() . ")</div></li>\n";
            }
            catch (midcom_error $e){}
        }
        echo "</ul></div>\n";
    }
}
?>