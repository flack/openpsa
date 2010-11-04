'smtp_host' => null,
'smtp_port' => null,
'default_try_backends' => array('mail_smtp', 'mail_sendmail', 'mail'),
'sendmail_path' => null,
'sendmail_args' => null,
'incorrect_charset_domains' => array(
        //Theses domains report character set incorrectly (thus messing up the conversion if we trust it)
        'yahoo.com',
    ),
'mb_detect_encoding_list' => 'ASCII,JIS,UTF-8,ISO-8859-1,EUC-JP,SJIS',
'iconv_append_target' => '//TRANSLIT', //Transliterate by default (other valid options '//IGNORE' or null)
