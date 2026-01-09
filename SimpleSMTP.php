<?php
// SimpleSMTP.php - A lightweight SMTP client for Gmail
class SimpleSMTP {
    private $host = 'smtp.gmail.com';
    private $port = 587;
    private $username;
    private $password;
    private $socket;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
        file_put_contents("smtp_debug.log", "SMTP Init: $username\n");
    }

    public function send($to, $subject, $body) {
        try {
            // 1. Connect
            $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 10);
            if (!$this->socket) throw new Exception("Connection failed: $errstr ($errno)");
            $this->read();

            // 2. Handshake
            $this->cmd("EHLO " . gethostname());
            $this->cmd("STARTTLS");
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd("EHLO " . gethostname());

            // 3. Auth
            $this->cmd("AUTH LOGIN");
            $this->cmd(base64_encode($this->username));
            $this->cmd(base64_encode($this->password));

            // 4. Send Mail
            $this->cmd("MAIL FROM: <" . $this->username . ">");
            $this->cmd("RCPT TO: <$to>");
            $this->cmd("DATA");

            // Headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: Sunday School <" . $this->username . ">\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";

            // Body
            $this->cmd($headers . "\r\n" . $body . "\r\n.");

            // 5. Quit
            $this->cmd("QUIT");
            fclose($this->socket);
            return true;

        } catch (Exception $e) {
            file_put_contents("smtp_debug.log", "SMTP Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    private function cmd($command) {
        // Don't log password
        $logCmd = (strpos($command, base64_encode($this->password)) !== false) ? "AUTH PASSWORD ***" : $command;
        file_put_contents("smtp_debug.log", "> $logCmd\n", FILE_APPEND);
        
        fputs($this->socket, $command . "\r\n");
        $response = $this->read();
        
        file_put_contents("smtp_debug.log", "< $response\n", FILE_APPEND);
        return $response;
    }

    private function read() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }
}
?>
