<?php
midcom::get()->auth->require_admin_user();
?>
<h1>Clean up old contents</h1>
<?php
$cleanup = new org_openpsa_directmarketing_cleanup();

if (midcom_baseclasses_components_configuration::get('org.openpsa.directmarketing', 'config')->get('delete_older')) {
    if (isset($_POST['erase_older'])) {
        $cleanup->delete();
    }

    echo "<form method=\"post\" action=\"\">\n";
    echo "    <input type=\"submit\" name=\"erase_older\" value=\"Clean up old entries\" />\n";
    echo "</form>\n";
} else {
    echo "<p>Automatic cleanup disabled by configuration.</p>\n";
}

// Show count
$cleanups = $cleanup->count();
$cleanups_kept = $cleanup->count(true);
echo "<table>\n";
echo "    <thead>\n";
echo "        <tr>\n";
echo "            <th>Type</th>\n";
echo "            <th>To clean</th>\n";
echo "            <th>To keep</th>\n";
echo "        </tr>\n";
echo "    </thead>\n";
echo "    <tbody>\n";
foreach ($cleanups as $type => $count) {
    echo "        <tr>\n";
    echo "            <th>{$type}</th>\n";
    echo "            <td style=\"text-align: right;\">" . number_format($count) . "</td>\n";
    if (isset($cleanups_kept[$type])) {
        echo "            <td style=\"text-align: right;\">" . number_format($cleanups_kept[$type]) ."</td>\n";
    }
    echo "        </tr>\n";
}
echo "    </tbody>\n";
echo "</table>\n";
?>