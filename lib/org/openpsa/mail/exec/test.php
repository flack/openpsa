<?php
$_MIDCOM->auth->require_admin_user();

echo "<p>\n";

$mail = new org_openpsa_mail();

if (   !isset($_POST['to'])
    || empty($_POST['to'])
    || !isset($_POST['from'])
    && empty($_POST['from']))
{
?>
    <h2>Send test email</h2>
    <form method="post">
        <p>
            Backend: <select name="backend">
                        <option value="try_default">Component default(s)</option>
                        <option value="mail_smtp">PEAR Mail/SMTP</option>
                        <option value="mail_sendmail">PEAR Mail/Sendmail</option>
                        <option value="mail">PHP mail()</option>
                     </select><br/>
            From: <input name="from" size=50 type="text" value="noreply@openpsa.org"/><br/>
            To: <input name="to" size=50 type="text" value="test@nemein.com" /><br/>
            Subject: <input name="subject" size=50 type="text" value="Testing o.o.mail with special chars (ÄäÖöÅå€)"/><br/>
            Message:<br/>
            <textarea rows=40 cols=80 name="body">Test body with special chars (Ää Öö Åå €)

/Test person</textarea><br/>
            <input type="submit" value="Send" />
        </p>
    </form>
<?php
}
else
{
    $mail->subject = $_POST['subject'];
    $mail->body = $_POST['body'];
    $mail->to = $_POST['to'];
    $mail->from = $_POST['from'];
    $ret = $mail->send($_POST['backend']);

    echo "mail->send returned {$ret}<br>\n";
}

echo "</p>\n";
?>