<?php
if (isset($_POST['n_level']))
{
    $level = $_POST['n_level'] + 1;
    $parent = $_POST['nodeid'];
}
else
{
    $parent = "null";
    $level = 0;
}

$records = sizeof($data['directories']) + sizeof($data['documents']);
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

echo "<rows>";
echo "<page>1</page>";
echo "<total>1</total>";
echo "<records>" . $records . "</records>";

//parent_directory
if (isset($data['parent_directory']))
{
    $icon = MIDCOM_STATIC_URL . '/stock-icons/16x16/up.png';
    $length = strlen($data['parent_directory']->name);
    $link = substr($prefix, 0, strlen($prefix) - (strlen($data['parent_directory']->name) + 1));
    echo "<row id='" . $data['parent_directory']->guid . "'>";
    echo "<cell></cell>";

    $link_html = "<![CDATA[";
    $link_html .= "<a href='" . $data['parent_up_link'] ."'>";
    $link_html .= "<img class='folder_icon' src='" . $icon . "' />";
    $link_html .= "<span >..</span></a>";
    $link_html .= "]]>";
    echo "<cell>" . $link_html ."</cell>";
    echo "<cell></cell>";

    //no creator_index/creator, last mod & file_size_index, file_size for parent_directory
    echo "<cell></cell>";
    echo "<cell></cell>";
    echo "<cell></cell>";
    echo "<cell></cell>";
    echo "<cell></cell>";
    echo "<cell></cell>";

    echo "<cell>0</cell>";
    echo "<cell>0</cell>";
    echo "<cell>true</cell>";
    echo "<cell>false</cell>";
    echo "</row>\n";
}

$path = "";
if (isset($data['parent_link']))
{
    $path = $data['parent_link'];
}

$icon = MIDCOM_STATIC_URL . '/stock-icons/16x16/folder.png';

foreach ($data['directories'] as $directory)
{
    echo "<row id='" . $directory->guid ."'>";
    echo "<cell><![CDATA[" . $directory->extra . "]]></cell>";

    $link_html = "<![CDATA[";
    $link_html .= "<a href='" . $prefix . $path . $directory->name . "/'>";
    $link_html .= "<img class='folder_icon' src='" . $icon . "' />";
    $link_html .= "<span>" . $directory->extra . "</span></a>";
    $link_html .= "]]>";
    echo "<cell>" . $link_html ."</cell>";
    echo "<cell></cell>";

    //creator_index/creator, last modified, filesize
    $creator = org_openpsa_widgets_contact::get($directory->metadata->creator);
    echo "<cell>" . $creator->contact_details['lastname'] . ", " . $creator->contact_details['firstname'] . "</cell>";
    echo "<cell><![CDATA[<span class='jqgrid_person'>" . $creator->show_inline() . "</span>]]></cell>";
    echo "<cell> " . $directory->metadata->revised . "</cell>";
    echo "<cell><![CDATA[<span class='jqgrid_date'>" . date("d.m.Y H:m", $directory->metadata->revised) . "</span>]]></cell>";
    echo "<cell>-1</cell>";
    echo "<cell></cell>";

    echo "<cell>" . $level . "</cell>";
    echo "<cell>" . $parent . "</cell>";
    // leaf = false, expanded = false
    echo "<cell>false</cell>";
    echo "<cell>false</cell>";
    echo "</row>\n";
}
foreach ($data['documents'] as $document)
{
    $file_size = 0;
    $download_url = '';
    $icon = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
    if ($data['datamanager']->autoset_storage($document))
    {
        $attach = array_shift($data['datamanager']->types['document']->attachments_info);
        if ($attach)
        {
            $file_size = $attach['filesize'];
            $icon = midcom_helper_misc::get_mime_icon($attach['mimetype']);
            if (!$file_size)
            {
                $file_size = 0;
            }
            $download_url = $attach['url'];
        }
    }
    echo "<row id='" . $document->guid ."'>";
    echo "<cell><![CDATA[" . $document->title . "]]></cell>";

    $class = 'document ' . $document->get_class();
    $link_html = "<![CDATA[";
    $link_html .= "<a href='" . $prefix . $path . "document/" . $document->guid . "/' class='" . $class . "'>";
    $link_html .= "<img class='document_icon' src='" . $icon . "' />";
    $link_html .= "<span>" . $document->title . "</span></a>";
    $link_html .= "]]>";
    echo "<cell>" . $link_html ."</cell>";
    echo "<cell> " . $download_url . "</cell>";
    //set contact-widget
    if (empty($document->author))
    {
        $author = org_openpsa_widgets_contact::get($document->metadata->creator);
    }
    else
    {
        $author = org_openpsa_widgets_contact::get($document->author);
    }
    // creator_index, creator-vcard & revised date
    echo "<cell>" . $author->contact_details['lastname'] . ", " . $author->contact_details['firstname'] . "</cell>";
    echo "<cell><![CDATA[<span class='jqgrid_person'>" . $author->show_inline() . "</span>]]></cell>";
    echo "<cell> " . $document->metadata->revised . "</cell>";
    echo "<cell><![CDATA[<span class='jqgrid_date'>" . date("d.m.Y H:m", $document->metadata->revised) . "</span>]]></cell>";

    //filesize-index & modified file_size
    echo "<cell>" . $file_size . "</cell>";
    echo "<cell><![CDATA[<span class='jqgrid_size'>" . midcom_helper_misc::filesize_to_string($file_size) . "</span>]]></cell>";

    //level & parent of document
    echo "<cell>" . $level . "</cell>";
    echo "<cell>" . $parent . "</cell>";

    // leaf = true, expanded = false
    echo "<cell>true</cell>";
    echo "<cell>false</cell>";
    echo "</row>";
}
echo "</rows>";
?>