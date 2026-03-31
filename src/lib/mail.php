<?php

/**
 * Send emails via SMTP or PHP mail.
 */
if (!defined('GRINDS_APP')) exit;

class SimpleMailer
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $encryption;

    /** Initialize SMTP settings. */
    public function __construct($config = [])
    {
        $this->host = $config['smtp_host'] ?? get_option('smtp_host');
        $this->port = $config['smtp_port'] ?? get_option('smtp_port', 587);
        $this->user = $config['smtp_user'] ?? get_option('smtp_user');

        // Handle password
        if (isset($config['smtp_pass'])) {
            $this->pass = $config['smtp_pass'];
        } else {
            $this->pass = grinds_decrypt(get_option('smtp_pass'));
        }

        $this->from = $config['smtp_from'] ?? get_option('smtp_from');
        $this->encryption = $config['smtp_encryption'] ?? get_option('smtp_encryption', 'tls');
    }

    /** Send email. */
    public function send($to, $subject, $body)
    {
        $cleanFrom = str_replace(["\r", "\n"], '', $this->from);
        $cleanTo = str_replace(["\r", "\n"], '', $to);
        $cleanSubject = str_replace(["\r", "\n"], '', $subject);

        // Fallback to PHP mail
        if (empty($this->host)) {
            $headers = "";
            if (!empty($cleanFrom)) {
                $headers = "From: " . $cleanFrom . "\r\n";
            }
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: GrindsCMS\r\n";

            // Encode subject
            $encodedSubject = mb_encode_mimeheader($cleanSubject, 'UTF-8', 'B', "\r\n");

            $params = null;
            if (!empty($cleanFrom)) {
                $envelopeFrom = $cleanFrom;
                if (preg_match('/<([^>]+)>/', $cleanFrom, $matches)) {
                    $envelopeFrom = trim($matches[1]);
                }
                if (filter_var($envelopeFrom, FILTER_VALIDATE_EMAIL)) {
                    $params = "-f" . escapeshellarg($envelopeFrom);
                }
            }

            return mail($cleanTo, $encodedSubject, $body, $headers, $params);
        }

        // Connect to server
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);

        if (!$socket) throw new Exception("Connection failed: $errstr ($errno)");
        stream_set_timeout($socket, 5);

        $this->read($socket, "220");
        $ehloHost = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0];
        $ehloHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $ehloHost);
        $this->write($socket, "EHLO " . $ehloHost);
        $this->read($socket, "250");

        // Start TLS
        if ($this->encryption === 'tls') {
            $this->write($socket, "STARTTLS");
            $this->read($socket, "220");
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new Exception("SMTP Error: Failed to establish secure TLS connection.");
            }
            $this->write($socket, "EHLO " . $ehloHost);
            $this->read($socket, "250");
        }

        // Authenticate
        if (!empty($this->user) && !empty($this->pass)) {
            $this->write($socket, "AUTH LOGIN");
            $this->read($socket, "334");
            $this->write($socket, base64_encode($this->user));
            $this->read($socket, "334");
            $this->write($socket, base64_encode($this->pass));
            $this->read($socket, "235");
        }

        // Send commands
        // Extract pure email address if "Name <email>" format is used
        $mailFromAddress = $cleanFrom;
        if (preg_match('/<([^>]+)>/', $cleanFrom, $matches)) {
            $mailFromAddress = trim($matches[1]);
        }

        $this->write($socket, "MAIL FROM: <{$mailFromAddress}>");
        $this->read($socket, "250");
        $this->write($socket, "RCPT TO: <$cleanTo>");
        $this->read($socket, "250");
        $this->write($socket, "DATA");
        $this->read($socket, "354");

        // Encode subject
        $encodedSubject = mb_encode_mimeheader($cleanSubject, 'UTF-8', 'B', "\r\n");

        $headers = "From: {$cleanFrom}\r\n";
        $headers .= "To: <$cleanTo>\r\n";
        $headers .= "Subject: " . $encodedSubject . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "Date: " . date("r") . "\r\n";
        $headers .= "X-Mailer: GrindsCMS\r\n";

        $body = preg_replace('/(?<!\r)\n/', "\r\n", $body);
        $body = str_replace("\r\r\n", "\r\n", $body);
        $body = preg_replace('/^\./m', '..', $body);

        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $this->read($socket, "250");

        $this->write($socket, "QUIT");
        $this->read($socket, "221");
        fclose($socket);

        return true;
    }

    /** Write command. */
    private function write($socket, $cmd)
    {
        fwrite($socket, $cmd . "\r\n");
    }

    /** Read response. */
    private function read($socket, $expect)
    {
        $response = "";
        $startTime = time();

        while (!feof($socket)) {
            if (time() - $startTime > 5) {
                throw new Exception("SMTP Error: Read timeout waiting for {$expect}");
            }

            // RFC 5321 specifies maximum reply line length of 512 octets including CRLF
            $str = fgets($socket, 515);
            if ($str === false) {
                break;
            }
            $response .= $str;
            if (strlen($str) >= 4 && substr($str, 3, 1) === ' ') {
                break;
            }
        }

        $meta = stream_get_meta_data($socket);
        if ($meta['timed_out']) {
            throw new Exception("SMTP Error: Connection timed out while reading response.");
        }

        if (!str_starts_with($response, $expect)) {
            throw new Exception("SMTP Error: Expected {$expect}, got {$response}");
        }
        return $response;
    }
}
