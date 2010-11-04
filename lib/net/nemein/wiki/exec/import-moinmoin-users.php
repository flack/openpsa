<?php
$_MIDCOM->auth->require_admin_user();
// Get us to full live mode
$_MIDCOM->cache->content->enable_live_mode();
while(@ob_end_flush());

$_MIDCOM->load_library('org.openpsa.mail');
?>
<h1>Notify MoinMoin wiki users</h1>
<?php
if (array_key_exists('directory', $_POST))
{
    $directory = dir($_POST['directory']);
    $moinmoin_users = array();
        
    while (false !== ($entry = $directory->read())) 
    {
        if (substr($entry, 0, 1) == '.')
        {
            // Ignore dotfiles
            continue;
        }
        
        $file = "{$_POST['directory']}/{$entry}";

        if (is_dir($file))
        {
            // Folder. Skip
            continue;
        }
        
        $path_parts = pathinfo($file);

        if (   array_key_exists('extension', $path_parts)
            && $path_parts['extension'] == 'trail')
        {
            // User page trail file, skip
            continue;
        }
        
        // This is a user file. Parse.
        $user = array();
        $lines = explode("\n", file_get_contents($file));
        foreach ($lines as $line)
        {
            if (substr($line, 0, 1) == '#')
            {
                // This is a comment line, skip
                continue;
            }
        
            $parts = explode('=', $line);
            if (count($parts) != 2)
            {
                // This is not valid config field, skip
                continue;
            }
            
            $property = trim($parts[0]);
            $value = trim($parts[1]);
            
            switch ($property)
            {
                case 'name':
                    $user['name'] = $value;
                    break;

                case 'email':
                    $user['email'] = strtolower($value);
                    break;
            }
        }
        
        if (array_key_exists('email', $user))
        {
            // We know the email so this is a user
            $moinmoin_users[$user['email']] = $user['name'];
        }
    }        
    $directory->close();
    
    echo "<p>Found total " . count($moinmoin_users) . " users to process</p>\n";
    
    // Try to match with existing emails
    $midgard_users = array();
    $qb = midcom_db_person::new_query_builder();
    $qb->add_constraint('email', '<>', '');
    $persons = $qb->execute();
    foreach ($persons as $person)
    {
        $midgard_users[strtolower($person->email)] = $person->name;
    }
    
    $persons_found = array();
    $persons_new = array();
    
    foreach ($moinmoin_users as $email => $user)
    {
        if (array_key_exists($email, $midgard_users))
        {
            // This person is already in DB
            $persons_found[$email] = $midgard_users[$email];
        }
        else
        {
            // This is a new person
            $persons_new[$email] = $user;
        }
    }
    
    echo "<p>" . count($persons_found) . " users are already in database and " . count($persons_new) . " are new</p>\n";
    
    foreach ($persons_new as $email => $name)
    {
        $mail = new org_openpsa_mail();
        $mail->to = $email;

        $mail->from = $_POST['from'];
        
        $mail->subject = $_POST['subject'];
        
        // Make replacements to body
        $message = str_replace('__USER__', $name, $_POST['message']);
        $mail->body = $message;
        
        $ret = $mail->send();
        if (!$ret)
        {
            echo "<p>failed to send notification email to {$mail->to}, reason: " . $mail->get_error_message() . "</p>\n";
        }
        else
        {
            echo "<p>Sent notification to {$name} ({$email})</p>\n";
        }
    }
}
else
{
    ?>
    <form method="post">
        <label>
            <span>Directory path for the user files</span>
            <input type="text" name="directory" />
        </label>
        <label>
            <span style="display: block;">Message From address</span>
            <input type="text" name="from" value="" />
        </label>
        <label>
            <span style="display: block;">Message subject</span>
            <input type="text" name="subject" value="Migration from MoinMoin to Midgard Wiki" />
        </label>
        <label>
            <span style="display: block;">Message to send to the users</span>
            <textarea name="message" rows="12" cols="60">
Dear MoinMoin wiki user __USER__,

The Wiki you're using is now switching to Midgard Wiki
software.

Wiki contents have been migrated but we have not migrated
user accounts automatically. Therefore to gain editing
access you should

Regards,
Your wiki team

More information:

http://www.midgard-project.org/documentation/net-nemein-wiki
            </textarea>
        </label>
        <div class="form_toolbar">
            <input type="submit" value="Send messages" />
        </div>
    </form>
    <?php
}
?>