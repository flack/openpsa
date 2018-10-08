'default_try_backends' => [
    'mail_sendmail',
    'mail_smtp',
    'mail',
],

//This becomes the fifth parameter of mail()
'mail_params' => [
    'additional_params' => ''
],

//These are passed to the SMTP transport
'mail_smtp_params' => [
    'host' => null,
    'port' => null
],

//These are passed to the Sendmail transport
'mail_sendmail_params' => [
    'sendmail_path' => '/usr/sbin/sendmail',
    'sendmail_args' => '-bs',
],

'schemadb_formmailer' => 'file:/org/openpsa/mail/config/schemadb_formmailer.inc',
'formmailer_from' => 'formmailer@openpsa2.org',
'formmailer_to' => '',
'formmailer_subject' => 'New form submission',
'formmailer_body' => '
Submitted form data:

Subject: __SUBJECT__

Message: __MESSAGE__
',