'default_try_backends' => [
    'mail_sendmail',
    'mail_smtp'
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

'swift_plugins' => [],

'schemadb_formmailer' => 'file:/org/openpsa/mail/config/schemadb_formmailer.inc',
'formmailer_from' => 'formmailer@openpsa2.org',
'formmailer_to' => '',
'formmailer_subject' => 'New form submission',
'formmailer_body' => '
Submitted form data:

Subject: __SUBJECT__

Message: __MESSAGE__
',