<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 9/12/14
 * Time: 12:17 PM
 */

namespace mpf\helpers;


use mpf\base\Helper;

class MailHelper extends Helper {

    /**
     * It can be replaced from config to use another mailing engine.
     * Here must be defined the function to be called in that case.
     * @var callable
     */
    public $method;

    /**
     * List of addresses to be used when sending an email. Multiple addresses can be defined
     * Examples: 'default', 'errors', 'contact', 'orders' and so on
     * @var array
     */
    public $from = [
        'default' => [
            'email' => 'me@domain.com',
            'name' => 'MPF App',
            'reply-to' => [
                'email' => 'me@domain.com',
                'name' => 'MPF App'
            ]
        ]
    ];

    /**
     * Any other method used to send email must accept this parameters.
     * @param string|array $to Simple email or an array('email' => .., 'name' => ..) or a list of recipients
     * @param string|array $from Same as $to plus option to define reply-to address. It will usually be filled from $this->from value.
     * @param string $subject Email subject. A simple string that will be sent as it is
     * @param string $message Email content.
     * @param array $attachments List of paths to attachments and name of the attachment as key
     * @param array $headerExtra Extra header options. Optional.
     * @param boolean $html
     * @return boolean
     */
    protected function _mail($to, $from, $subject, $message, $attachments = array(), $headerExtra = array(), $html = true) {
        $boundary = "";
        $fromText = $from['name'] . '<' . $from['email'] . '>';
        $headers = array();
        $headers[] = "From: $fromText";
        if (isset($from['reply-to'])) {
            $headers[] = "Reply-To: {$from['reply-to']['name']}<{$from['reply-to']['email']}>";
        }
        $headers[] = "MIME-Version: 1.0";
        if ($headerExtra) {
            foreach ($headerExtra as $name => $value) {
                $headers[] = "$name: $value";
            }
        }
        if (count($attachments)) {
            $boundary = md5(uniqid(time()));
            $headers[] = "Content-type: multipart/mixed; boundary=\"$boundary\"";
            $headers[] = "";
            $headers[] = "This is a multi-part message in MIME format.";
            $headers[] = "--$boundary";
            $headers[] = "Content-type: text/" . ($html ? 'html' : 'plain') . ";charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: 7bit";
            $headers[] = "";
            $headers[] = $message;
            $headers[] = "";
            $headers[] = "--$boundary";
        } else {
            $headers[] = "Content-type: text/" . ($html ? 'html' : 'plain') . ";charset=UTF-8";
        }

        foreach ($attachments as $name => $path) { // if key is name then use it for attachment, if not it will use file name
            $type = str_replace($path . ':', '', exec('file -i ' . $path));
            $type = explode(';', $type);
            $type = $type[0];
            $headers[] = "Content-Type: $type; name=\"" . (is_numeric($name) ? basename($path) : $name) . "\"";
            $headers[] = "Content-Transfer-Encoding: base64";
            $headers[] = "Content-Disposition: attachment; filename=\"" . (is_numeric($name) ? basename($path) : $name) . "\"";
            $headers[] = "";
            $contents = fread($h = fopen($path, 'rb'), filesize($path));
            fclose($h);
            $headers[] = chunk_split(base64_encode($contents));
            $headers[] = "";
            $headers[] = "--$boundary";
        }
        if (count($attachments)) {
            $headers[count($headers) - 1] .= "--";
        }

        if (is_array($to)) { // process from array to string. It will check also for multiple addresses.
            if (isset($to['email'])) {
                $to = array($to);
            }
            $toFinal = array();
            foreach ($to as $address) {
                $toFinal[] = is_array($address) ? "$address[name]<$address[email]>" : $address;
            }
            $to = implode(",", $toFinal);
        }

        $m = mail($to, $subject, count($attachments) ? strip_tags($message) : $message, implode("\r\n", $headers));
        return $m;
    }

    /**
     * Send simple message with no attachments
     * @param $to
     * @param $subject
     * @param $message
     * @param string $from
     * @param array $headerExtra
     * @param boolean $html
     * @return bool
     */
    public function send($to, $subject, $message, $from = 'default', $headerExtra = array(), $html = true) {
        if (!is_null($this->method)) { // if a method is set then use that one
            return $this->method($to, is_string($from) ? $this->from[$from] : $from, $subject, $message, array(), $headerExtra, $html);
        } // if not use default
        return $this->_mail($to, is_string($from) ? $this->from[$from] : $from, $subject, $message, array(), $headerExtra, $html);
    }

    /**
     * Send message with attachments
     * @param $to
     * @param $subject
     * @param $message
     * @param $attachments
     * @param string $from
     * @param array $headerExtra
     * @param boolean $html
     * @return bool
     */
    public function sendAttachments($to, $subject, $message, $attachments, $from = 'default', $headerExtra = array(), $html = true) {
        if (!is_null($this->method)) {
            return $this->method($to, is_string($from) ? $this->from[$from] : $from, $subject, $message, $attachments, $headerExtra, $html);
        }
        return $this->_mail($to, is_string($from) ? $this->from[$from] : $from, $subject, $message, $attachments, $headerExtra, $html);
    }
} 