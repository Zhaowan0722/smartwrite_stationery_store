<?php
require_once __DIR__ . '/mail-config.php';

function smartwrite_smtp_read($socket) {
    $data = '';
    while ($str = fgets($socket, 515)) {
        $data .= $str;
        if (isset($str[3]) && $str[3] == ' ') {
            break;
        }
    }
    return $data;
}

function smartwrite_smtp_command($socket, $command, $expectedCode) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smartwrite_smtp_read($socket);
    $code = substr($response, 0, 3);
    if ($code != (string)$expectedCode) {
        throw new Exception("SMTP error. Command: " . ($command ?? 'CONNECT') . " Response: " . $response);
    }
    return $response;
}

function smartwrite_send_email($toEmail, $toName, $subject, $htmlBody, $plainBody = '') {
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USERNAME, $SMTP_PASSWORD, $SMTP_FROM_EMAIL, $SMTP_FROM_NAME;

    if ($SMTP_USERNAME === 'your_email@gmail.com' || $SMTP_PASSWORD === 'your_16_digit_app_password') {
        throw new Exception('Please update includes/mail-config.php with your Gmail and Gmail App Password first.');
    }

    if ($plainBody === '') {
        $plainBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
    }

    $socket = fsockopen($SMTP_HOST, $SMTP_PORT, $errno, $errstr, 30);
    if (!$socket) {
        throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
    }

    stream_set_timeout($socket, 30);

    try {
        smartwrite_smtp_command($socket, null, 220);
        smartwrite_smtp_command($socket, 'EHLO localhost', 250);
        smartwrite_smtp_command($socket, 'STARTTLS', 220);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception('Could not start TLS encryption. Make sure PHP OpenSSL extension is enabled.');
        }

        smartwrite_smtp_command($socket, 'EHLO localhost', 250);
        smartwrite_smtp_command($socket, 'AUTH LOGIN', 334);
        smartwrite_smtp_command($socket, base64_encode($SMTP_USERNAME), 334);
        smartwrite_smtp_command($socket, base64_encode(str_replace(' ', '', $SMTP_PASSWORD)), 235);

        smartwrite_smtp_command($socket, 'MAIL FROM:<' . $SMTP_FROM_EMAIL . '>', 250);
        smartwrite_smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', 250);
        smartwrite_smtp_command($socket, 'DATA', 354);

        $boundary = 'b' . md5(uniqid(time(), true));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($SMTP_FROM_NAME) . '?=';
        $encodedToName = $toName ? '=?UTF-8?B?' . base64_encode($toName) . '?= ' : '';

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $encodedFromName . ' <' . $SMTP_FROM_EMAIL . '>';
        $headers[] = 'To: ' . $encodedToName . '<' . $toEmail . '>';
        $headers[] = 'Subject: ' . $encodedSubject;
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $plainBody . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        $message .= '--' . $boundary . "--\r\n";


        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = str_replace("\n", "\r\n", $message);

        $message = preg_replace('/(^|\r\n)\./', '$1..', $message);
        fwrite($socket, $message . "\r\n.\r\n");
        $response = smartwrite_smtp_read($socket);
        $code = substr($response, 0, 3);
        if ($code != '250') {
            throw new Exception('SMTP error after sending email data. Response: ' . $response);
        }
        smartwrite_smtp_command($socket, 'QUIT', 221);
        fclose($socket);
        return true;

    } catch (Exception $e) {
        fclose($socket);
        throw $e;
    }
}
?>
