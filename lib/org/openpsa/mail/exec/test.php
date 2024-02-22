<?php
use Symfony\Component\HttpFoundation\Request;

midcom::get()->auth->require_admin_user();
$post = Request::createFromGlobals()->request;

if (   !$post->get('to')
    || !$post->get('from')) {
    ?>
    <h2>Send test email</h2>
    <form method="post">
        <p>
            Backend: <select name="backend">
                        <option value="try_default">Component default(s)</option>
                        <option value="mail_smtp">SMTP</option>
                        <option value="mail_sendmail">Sendmail</option>
                     </select><br/>
            From: <input name="from" size=50 type="text" value="noreply@openpsa2.org"/><br/>
            To: <input name="to" size=50 type="text" value="test@openpsa2.org" /><br/>
            Subject: <input name="subject" size=50 type="text" value="Testing o.o.mail with special chars (ÄäÖöÅå€)"/><br/>
            Message:<br/>
            <textarea rows=40 cols=80 name="body">Test body with special chars (Ää Öö Åå €)

/Test person</textarea><br/>
            <input type="submit" value="Send" />
        </p>
    </form>
<?php

} else {
    $mail = new org_openpsa_mail($post->get('backend'));

    $mail->subject = $post->get('subject');
    $mail->body = $post->get('body');
    $mail->to = $post->get('to');
    $mail->from = $post->get('from');

    $ret = $mail->send();
    echo "<p>mail->send returned {$ret}<br>\n";
    if (!$ret) {
        echo $mail->get_error_message();
    }
    echo "</p>\n";
}
?>