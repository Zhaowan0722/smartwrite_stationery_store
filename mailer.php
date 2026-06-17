<?php


require_once __DIR__ . '/send-mail.php';

function send_smartwrite_email($toEmail, $toName, $subject, $htmlBody)
{
    return smartwrite_send_email($toEmail, $toName, $subject, $htmlBody);
}
?>
