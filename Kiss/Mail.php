<?php
/**
 * Kiss Mail
 * =========
 * Class to send E-Mails in UTF8-Charset with PHP.
 * New in Version 1.3: Optional sending via an SMTP Server. - Depends on PEAR::Mail
 * New in Version 1.4: 8bit encoding is applied without imap_8bit().
 * New in Version 2: Complete rewrite of the class + better support of SMTP + unit tests
 * New in Version 3: Ported to the Kiss namespace, method names rewritten to camelCase.
 * @author Christian Engel <hello@wearekiss.com>
 * @version 3 Apr 6th, 2013
 */

namespace Kiss;

class Mail {
    private $sendSeparate = TRUE;
    private $htmlMode = FALSE;
    private $smtpData = NULL;
    private $sender = '';
    private $receivers = array();
    private $bcc = array();
    private $cc = array();
    private $attachments = array();
    private $report = '';

    /**
     * Will test for a correct mail address.
     * Will return the mail on success, or an empty string, if its not a mail.
     * @param {String} $email
     * @return {String}
     */
    function checkEmail($email) {
        if (preg_match('/^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-+])+\.)+([a-zA-Z0-9]{2,4})+$/', $email)) {
            return $email;
        }
        return '';
    }

    function __constructor($sendAddress = '') {
        if ($sendAddress) {
            $this->setSender($sendAddress);
        }
    }

    function setSender($sendAddress) {
        if (!$this->checkEmail($sendAddress)) {
            throw new \ErrorException('This is not an E-Mail: ' . $sendAddress);
        }
        $this->sender = $sendAddress;
    }

    function getSender() {
        return $this->sender;
    }

    /**
     * Pass SMTP login data to this function to use a SMTP server to send your mails, instead of the basic PHP mail function.
     * If you want to use SSL, prefix your hostname with "ssl://", if you want to use SSL + TLS, prefix your hostname with "tls://".
     * @param {String} $host SMTP server hostname. Prefix with either "ssl://" or "tls://", and optionally set a custom port like so: "myserver.com:31"
     * @param {String} $username Your SMTP username
     * @param {String} $password Your user password
     */
    function setSmtp($host, $username, $password) {
        $parts = explode(':', $host);
        $port = 25;
        if (substr($host, 0, 6) == 'ssl://' || substr($host, 0, 6) == 'tls://') {
            $port = 465;
            if (count($parts) == 3) {
                $port = (int)array_pop($parts);
            }
        }
        else {
            if (count($parts) == 2) {
                $port = (int)array_pop($parts);
            }
        }
        $host = implode(':', $parts);

        $this->smtpData = array(
            'host' => $host,
            'port' => $port,
            'user' => $username,
            'password' => $password
        );
    }

    function getSmtp() {
        return $this->smtpData;
    }

    /**
     * Will add a regular receiver to the E-Mail.
     * @param {String} $mail
     * @throws Exception
     */
    function addReceiver($mail) {
        if (!$this->checkEmail($mail)) {
            throw new \ErrorException('This is not an E-Mail: ' . $mail);
        }
        $this->receivers[] = $mail;
    }

    function getReceivers() {
        return $this->receivers;

    }

    /**
     * This will add a CC-Receiver to the E-Mail.
     * @param $mail
     * @throws Exception
     */
    function addCc($mail) {
        if (!$this->checkEmail($mail)) {
            throw new \ErrorException('This is not an E-Mail: ' . $mail);
        }
        $this->cc[] = $mail;
    }

    function getCc() {
        return $this->cc;
    }

    /**
     * Will add a new BCC-Receiver to the E-Mail.
     * @param $mail
     * @throws Exception
     */
    function addBcc($mail) {
        if (!$this->checkEmail($mail)) {
            throw new \ErrorException('This is not an E-Mail: ' . $mail);
        }
        $this->bcc[] = $mail;
    }

    function getBcc() {
        return $this->bcc;
    }

    /**
     * This will flush all normal, cc and bcc-receiver lists.
     */
    function clearReceivers() {
        $this->receivers = $this->cc = $this->bcc = array();
    }

    /**
     * Will add a file as a attachment. Checks if the file exists and will throw an error otherwise.
     * @param {String} $sourceFilename
     * @param {String}  $targetFilename (optional) New filename inside the E-Mail
     * @return bool
     * @throws Exception
     */
    function addAttachment($sourceFilename, $targetFilename = NULL) {
        if (file_exists($sourceFilename)) {
            if (!$targetFilename) {
                $targetFilename = basename($sourceFilename);
            }
            $this->attachments[] = array(
                $sourceFilename,
                $targetFilename
            );
            return true;
        }
        else {
            throw new \ErrorException('File not found: ' . $sourceFilename);
        }
    }

    function clearAttachments() {
        $this->attachments = array();
    }

    /**
     * Pass true, to send E-Mails to each recipient separately, so mail-addresses are not visible to eachother.
     * Note: attached CC and BCC recipients are attached (and visible) to the FIRST sent e-mail.
     *
     * WARNING: sending a E-Mail in separate mode to many receivers is VERY slow, since a complete new E-Mail is generated
     *          for each receiver.
     *
     * By default, E-Mails are sent separately.
     * @param {Bool} $yesNo
     */
    function setModeSeparate($yesNo) {
        $this->sendSeparate = $yesNo;
    }

    /**
     * Pass true, to send a E-Mail containing HTML text. You need to activate HTML mode when you want to send HTML formatted
     * mails, otherwise this mails will display their source un-interpreted at the receivers end.
     * By default, E-Mails are sent in text mode.
     * @param {Bool} $yesNo
     */
    function setModeHtml($yesNo) {
        $this->htmlMode = $yesNo;
    }

    /**
     * Will send an E-Mail with the current configuration.
     * Heads up! SMTP mails are always sent without CC, BCC and Attachments. Mails sent via SMTP are ALWAYS sent separately.
     * @TODO: Add CC, BCC and attachment support to SMTP sending.
     * @param {String} $subject
     * @param {String} $body
     */
    function send($subject, $body) {
        if (!$this->sender) {
            throw new \ErrorException('No sender defined');
        }

        if (!count($this->receivers)) {
            throw new \ErrorException('No receivers defined');
        }

        //No SMTP data set? Send in normal mode.
        if ($this->smtpData === NULL) {
            if ($this->sendSeparate) {
                $cnt = 0;
                foreach ($this->receivers as $receiver) {
                    if (!$cnt) {
                        $this->sendNormal(array($receiver), $this->cc, $this->bcc, $this->attachments, $subject, $body);
                    }
                    else {
                        $this->sendNormal(array($receiver), array(), array(), $this->attachments, $subject, $body);
                    }
                    $cnt++;
                }
            }
            else {
                $this->sendNormal($this->receivers, $this->cc, $this->bcc, $this->attachments, $subject, $body);
            }
        }
        else {
            //SMTP Data set, send through SMTP function.
            if ($this->sendSeparate) {
                $cnt = 0;
                foreach ($this->receivers as $receiver) {
                    if (!$cnt) {
                        $this->sendSmtp(array($receiver), $this->cc, $this->bcc, $this->attachments, $subject, $body);
                    }
                    else {
                        $this->sendSmtp(array($receiver), array(), array(), $this->attachments, $subject, $body);
                    }
                    $cnt++;
                }
            }
            else {
                $this->sendSmtp($this->receivers, $this->cc, $this->bcc, $this->attachments, $subject, $body);
            }
        }
    }

    private function sendNormal(array $rec, array $cc, array $bcc, array $attch, $subject, $body) {
        $headers = array();
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Type'] = 'text/' . ($this->htmlMode ? 'html' : 'plain') . '; charset="UTF-8"';
        $headers['Content-Transfer-Encoding'] = '8bit';
        $headers['Date'] = date('r');
        $headers['From'] = $this->sender;
        if (count($cc)) {
            $headers['Cc'] = implode(',', $cc);
        }
        if (count($bcc)) {
            $headers['Bcc'] = implode(',', $bcc);
        }

        $header = '';
        foreach ($headers as $k => $v) {
            $header .= $k . ': ' . $v . "\r\n";
        }

        $body = $this->setAttachments() . $body;

        foreach ($rec as $r) {
            mail($r, '=?UTF-8?Q?' . $this->quotedPrintableEncode($subject) . '?=', $body, $header);
        }
    }

    private function sendSmtp(array $rec, array $cc, array $bcc, array $attch, $subject, $body) {
        $eol = "\r\n";
        $errno = NULL;
        $errstr = '';
        $log = array();

        $s = fsockopen($this->smtpData['host'], $this->smtpData['port'], $errno, $errstr);
        if ($s === FALSE) {
            throw new \ErrorException($errstr, $errno);
        }

        $r = fgets($s);
        $log[] = '< ' . $r;
        fputs($s, 'EHLO kSendmail2' . $eol);
        $log[] = '> EHLO kSendmail2';

        while ($r = @fgets($s)) {
            $log[] = '< ' . $r;
            if (substr($r, 3, 1) == ' ') {
                break;
            }
        }

        fputs($s, 'AUTH LOGIN' . $eol);
        $log[] = '> AUTH LOGIN';
        $r = fgets($s);
        $log[] = '< ' . $r;
        if (substr($r, 0, 3) != '334') {
            throw new \ErrorException('Unexpected Response: ' . $r);
        }
        fputs($s, base64_encode($this->smtpData['user']) . $eol);
        $log[] = '> ' . base64_encode($this->smtpData['user']);
        $r = fgets($s);
        $log[] = '< ' . $r;
        if (substr($r, 0, 3) != '334') {
            throw new \ErrorException('Unexpected Response: ' . $r);
        }
        fputs($s, base64_encode($this->smtpData['password']) . $eol);
        $log[] = '> ' . base64_encode($this->smtpData['password']);
        $r = fgets($s);
        $log[] = '< ' . $r;
        if (substr($r, 0, 3) != '235') {
            print_r($log);
            throw new \ErrorException('Login failed.');
        }

        fputs($s, 'MAIL FROM: <' . $this->sender . '>' . $eol);
        $r = fgets($s);
        if (substr($r, 0, 3) != '250') {
            throw new \ErrorException('Unexpected Response: ' . $r);
        }

        foreach (array_merge($rec, $cc, $bcc) as $r) {
            fputs($s, 'RCPT TO:<' . $r . '>' . $eol);
            $r = fgets($s);
            if (substr($r, 0, 3) != '250') {
                throw new \ErrorException('Unexpected Response: ' . $r);
            }
        }

        fputs($s, 'DATA' . $eol);
        $r = fgets($s);
        if (substr($r, 0, 3) != '354') {
            throw new \ErrorException('Unexpected Response: ' . $r);
        }

        //Build the mail headers.
        $headers = array();
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Type'] = 'text/' . ($this->htmlMode ? 'html' : 'plain') . '; charset="UTF-8"';
        $headers['Content-Transfer-Encoding'] = '8bit';
        $headers['Date'] = date('r');
        $headers['From'] = $this->sender;
        $headers['To'] = implode(',', $rec);
        if (count($cc)) {
            $headers['Cc'] = implode(',', $cc);
        }
        if (count($bcc)) {
            $headers['Bcc'] = implode(',', $bcc);
        }

        $headers['Subject'] = '=?UTF-8?Q?' . $this->quotedPrintableEncode($subject) . '?=';

        $header = '';
        foreach ($headers as $k => $v) {
            $header .= $k . ": " . $v . $eol;
        }
        $header .= $eol;

        $body = $this->setAttachments() . $body;

        fputs($s, $header . $body . $eol);

        fputs($s, '.' . $eol);

        $r = fgets($s);
        if (substr($r, 0, 3) != '250') {
            throw new \ErrorException('Unexpected Response: ' . $r);
        }
        fputs($s, 'QUIT');
        fclose($s);
    }

    /**
     * Prepares the attachments for shipment.
     */
    private function setAttachments() {
        $result = '';
        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $a) {
                $filename = $a[1];
                $f = fopen($a[0], "r");
                $content = fread($f, filesize($a[0]));
                fclose($f);
                $result .= "begin 666 " . $filename . "\r\n";
                $result .= convert_uuencode($content);
                $result .= "end\r\n";
            }
        }
        return $result;
    }

    /**
     * Encodes a string in 8Bit format.
     * @param {String} $sText
     * @param {Bool} $bEmulate_imap_8bit [optional]
     * @return {String}
     */
    private function quotedPrintableEncode($sText, $bEmulate_imap_8bit = true) {
        // split text into lines
        $aLines = explode(chr(13) . chr(10), $sText);

        for ($i = 0; $i < count($aLines); $i++) {
            $sLine = & $aLines[$i];
            if (strlen($sLine) === 0) {
                continue;
            } // do nothing, if empty

            $sRegExp = '/[^\x09\x20\x21-\x3C\x3E-\x7E]/e';

            // imap_8bit encodes x09 everywhere, not only at lineends,
            // for EBCDIC safeness encode !"#$@[\]^`{|}~,
            // for complete safeness encode every character :)
            if ($bEmulate_imap_8bit) {
                $sRegExp = '/[^\x20\x21-\x3C\x3E-\x7E]/e';
            }

            $sReplmt = 'sprintf( "=%02X", ord ( "$0" ) ) ;';
            $sLine = preg_replace($sRegExp, $sReplmt, $sLine);

            // encode x09,x20 at lineends
            {
                $iLength = strlen($sLine);
                $iLastChar = ord($sLine{$iLength - 1});

                //              !!!!!!!!
                // imap_8_bit does not encode x20 at the very end of a text,
                // here is, where I don't agree with imap_8_bit,
                // please correct me, if I'm wrong,
                // or comment next line for RFC2045 conformance, if you like
                if (!($bEmulate_imap_8bit && ($i == count($aLines) - 1))) {
                    if (($iLastChar == 0x09) || ($iLastChar == 0x20)) {
                        $sLine{$iLength - 1} = '=';
                        $sLine .= ($iLastChar == 0x09) ? '09' : '20';
                    }
                }
            } // imap_8bit encodes x20 before chr(13), too
            // although IMHO not requested by RFC2045, why not do it safer :)
            // and why not encode any x20 around chr(10) or chr(13)
            if ($bEmulate_imap_8bit) {
                $sLine = str_replace(' =0D', '=20=0D', $sLine);
                //$sLine=str_replace(' =0A','=20=0A',$sLine);
                //$sLine=str_replace('=0D ','=0D=20',$sLine);
                //$sLine=str_replace('=0A ','=0A=20',$sLine);
            }

            // finally split into softlines no longer than 76 chars,
            // for even more safeness one could encode x09,x20
            // at the very first character of the line
            // and after soft linebreaks, as well,
            // but this wouldn't be caught by such an easy RegExp
            preg_match_all('/.{1,73}([^=]{0,2})?/', $sLine, $aMatch);
            $sLine = implode('=' . chr(13) . chr(10), $aMatch[0]); // add soft crlf's
        }

        // join lines into text
        return implode(chr(13) . chr(10), $aLines);
    }

}
