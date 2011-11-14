<? 
/**
 * Klasse zum Versenden von E-Mails mit UTF-8 Zeichensatz via PHP.
 * Neu in Version 1.3: Versand über einen SMTP Server. - Benötigt PEAR::Mail
 * Neu in Version 1.4: 8bit encoding wird ohne imap_8bit() durchgeführt.
 * @author Christian Engel
 * @version 1.4
 *
 */
class kSendmail
{
    /**
     * @var string Mails einzeln an Absender zustellen.
     */
    const MAIL_SEPERATE = "SEPERATE";
    /**
     * @var Eine Mail mit zusammengefassten (für die Anderen sichtbaren) Empfängern verschicken.
     */
    const MAIL_TOGETHER = "TOGETHER";
    
    /**
     * @var Objekt für SMTP-Versand
     */
    private $smtpObject = null;
    
    /**
     * @var string Absender-Mailadresse
     */
    private $sender = "";
    /**
     * @var array Empfänger-Mailadressen
     */
    private $recievers = array();
    private $bcc = array();
    private $cc = array();
    /**
     * @var array Liste mit Pfaden für Dateianhänge.
     */
    private $attachments = array();
    /**
     * @var Soll nach jeder Mail ein kurzer Report ausgegeben werden?
     */
    private $varShowReport = false;
    
    /**
     * Konstruktor
     * Setzt die Absender-Adresse.
     * @param $send_address
     */
    function __constructor($send_address)
    {
        $this->setSender($send_address);
    }
    
    /**
     * Aktiviert den SMTP Versand.
     * @param string $host
     * @param string $username
     * @param string $password
     */
    function useSMTP($host, $username, $password)
    {
        try
        {
            require_once ("Mail.php");
            $this->smtpObject = Mail::factory('smtp', array('host'=>$host, 'auth'=>true, 'username'=>$username, 'password'=>$password));
        }
        catch(Exception $e)
        {
            echo "<b>ERROR:</b> Cannot create instance of PEAR::Mail";
            $this->smtpObject = null;
        }
    }
    
    /**
     * Überschreibt die im Konstruktor angebene Absenderadresse.
     * @param string $send_address
     */
    function setSender($send_address)
    {
        $this->sender = $send_address;
        echo $this->sender;
    }
    
    /**
     * Fügt einen neuen Empfänger hinzu.
     * @param string $reciever Mailadresse des Empfängers
     * @param string $mode Von welchem Typ soll der Empfänger sein?
     * Leer = Normaler Einzelempfänger.
     * BCC	= Blind Carbon-Copy
     * CC	= Carbon-Copy
     * @return boolean
     */
    function addReciever($reciever, $mode = "")
    {
        if ($this->checkMail($reciever))
        {
            switch ($mode)
            {
                case "BCC":
                    $this->bcc[] = $reciever;
                    break;
                case "CC":
                    $this->cc[] = $reciever;
                    break;
                default:
                    $this->recievers[] = $reciever;
            }
            return true;
        }
        else
            return false;
    }
    
    /**
     * Leert die Empfängerliste
     * @param string[optional] Welche Empfänger sollen geleert werden?
     * Leer = Normale Empfänger
     * CC = Carbon Copy
     * BCC = Blind Carbon Copy
     * * = Alle (default)
     */
    function clearRecievers($type = "*")
    {
        switch ($type)
        {
            case "*":
                $this->recievers = array();
                $this->bcc = array();
                $this->cc = array();
                break;
            case "CC":
                $this->cc = array();
                break;
            case "BCC":
                $this->bcc = array();
            default:
                $this->recievers = array();
        }
    }
    
    /**
     * Legt fest, ob ein Report nach dem Senden einer Mail angezeigt werden soll, oder nicht.
     * @param boolean $yesno
     */
    function showReport($yesno)
    {
        $this->varShowReport = (bool)$yesno;
    }
    
    /**
     * Versendet eine standard Textmail.
     * @param string $subject
     * @param string $text
     * @param string $mode Soll eine Mail mit allen Empfängern zusammengefasst verschickt werden, oder seperate Mails an jeden einzelnen Empfänger?
     * @param string $type plain oder html
     * @return boolean
     */
    function send($subject, $text, $mode = "SEPERATE", $type = "plain")
    {
        if ($type != "plain")
            $type = "html";
            
        if ($this->sender != "" && count($this->recievers) > 0)
        {
            if ($mode == "SEPERATE")
            {
                $cnt = 0;
                foreach ($this->recievers as $r)
                {
                    $cnt++;
                    $header = '';
                    $headers = array();
                    $headers["MIME-Version"] = "1.0";
                    $headers["From"] = $this->sender;
                    if ($cnt == 1)
                    {
                        //BCCs und CCs anhängen.
                        if (count($this->bcc) > 0)
                            $headers["Bcc"] = implode(",".$this->bcc);
                        if (count($this->cc) > 0)
                            $headers["Cc"] = implode(",".$this->cc);
                    }
                    $headers["Content-Type"] = "text/$type; charset=\"UTF-8\"";
                    $headers["Content-Transfer-Encoding"] = "8bit";
                    $totalText = $this->setAttachments().$text;
                    if ($this->smtpObject)
                    {
                        $headers["Subject"] = "=?UTF-8?Q?".$this->quoted_printable_encode($subject)."?=";
                        $mail = $this->smtpObject->send($r, $headers, $totalText);
                        if ($this->varShowReport && PEAR::isError($mail))
                        {
                            echo "<b>SMTP-Error:</b> ".$mail->getMessage();
                        }
                    }
                    else
                    {
                        foreach ($headers as $key=>$wert)
                        {
                            $header .= $key.": ".$wert."\r\n";
                        }
                        $header .= "\r\n";
                        mail($r, "=?UTF-8?Q?".$this->quoted_printable_encode($subject)."?=", $totalText, $header);
                    }
                    if ($this->varShowReport)
                        echo "Bericht: <br />".$r."<br />".$subject."<br />".$text."<br />".$header."<br />".count($this->attachments)." Anh�nge.";
                }
            }
            else
            {
                $r = $this->recievers[0];
                $restEmpfaenger = array_reverse($this->recievers);
                @array_pop($restEmpfaenger);
                $headers = array();
                $headers["MIME-Version"] = "1.0";
                $headers["From"] = $this->sender;
                if (count($restEmpfaenger) > 0)
                    $headers["To"] = implode(",".$restEmpfaenger);
                if (count($this->bcc) > 0)
                    $headers["Bcc"] = implode(",".$this->bcc);
                if (count($this->cc) > 0)
                    $headers["Cc"] = implode(",".$this->cc);
                $headers["Content-Type"] = "text/$type; charset=\"UTF-8\"";
                $headers["Content-Transfer-Encoding"] = "8bit";
                $totalText = $this->setAttachments().$text;
                
                if ($this->smtpObject)
                {
                    $headers["Subject"] = "=?UTF-8?Q?".$this->quoted_printable_encode($subject)."?=";
                    $mail = $this->smtpObject->send($r, $headers, $totalText);
                    if ($this->varShowReport && PEAR::isError($mail))
                    {
                        echo "<b>SMTP-Error:</b> ".$mail->getMessage();
                    }
                }
                else
                {
                    foreach ($headers as $key=>$wert)
                    {
                        $header .= $key.": ".$wert."\r\n";
                    }
                    $header .= "\r\n";
                    mail($r, "=?UTF-8?Q?".$this->quoted_printable_encode($subject)."?=", $totalText, $header);
                }
                
                if ($this->varShowReport)
                    echo "Bericht: <br />".$r."<br />".$subject."<br />".$text."<br />".$header."<br />".count($this->attachments)." Anh�nge.";
            }
            
            return true;
        }
        else
            return false;
    }
    
    /**
     * Bereitet die hinzugefügten Attachments für den Mailversand auf.
     */
    private function setAttachments()
    {
        $ausgabe = "";
        if (count($this->attachments) > 0)
        {
            foreach ($this->attachments as $a)
            {
                $dateiname = $a[1];
                $datei = fopen($a[0], "r");
                $inhalt = fread($datei, filesize($a[0]));
                fclose($datei);
                $ausgabe .= "begin 666 ".$dateiname."\r\n";
                $ausgabe .= convert_uuencode($inhalt);
                $ausgabe .= "end\r\n";
            }
        }
        return $ausgabe;
    }
    
    /**
     * Fügt eine Datei als Attachment hinzu. Es wird gepfüft ob die Datei existiert.
     * @param string $source_filename Quellpfad zur Datei
     * @param string $target_filename [optional] Neuer Dateiname im Anhang
     * @return boolean
     */
    function addAttachment($source_filename, $target_filename = NULL)
    {
        if (file_exists($source_filename))
        {
            if( ! $target_filename) $target_filename = basename($source_filename);
            $this->attachments[] = array($source_filename, $target_filename);
            return true;
        }
        else
            return false;
    }
    
    /**
     * Entfernt alle Attachments von der Mail.
     * Dateien werden NICHT physikalisch gelöscht!
     */
    function clearAttachments()
    {
        $this->attachments = array();
    }
    
    /**
     * Prüft, ob eine Mailadresse syntaktisch korrekt ist.
     * @param string $mailAddress
     * @return boolean
     */
    function checkMail($mailAddress)
    {
        return preg_match('/^[a-z0-9._%-]+@[a-z0-9.-]+\.[a-z]{2,4}$/', $mailAddress);
    }
    
    /**
     * Encodiert einen String im 8Bit format.
     * @param string $sText
     * @param boolean $bEmulate_imap_8bit [optional]
     * @return string
     */
    private function quoted_printable_encode($sText, $bEmulate_imap_8bit = true)
    {
        // split text into lines
        $aLines = explode(chr(13).chr(10), $sText);
        
        for ($i = 0; $i < count($aLines); $i++)
        {
            $sLine = &$aLines[$i];
            if (strlen($sLine) === 0)
                continue; // do nothing, if empty
                
            $sRegExp = '/[^\x09\x20\x21-\x3C\x3E-\x7E]/e';
            
            // imap_8bit encodes x09 everywhere, not only at lineends,
            // for EBCDIC safeness encode !"#$@[\]^`{|}~,
            // for complete safeness encode every character :)
            if ($bEmulate_imap_8bit)
                $sRegExp = '/[^\x20\x21-\x3C\x3E-\x7E]/e';
                
            $sReplmt = 'sprintf( "=%02X", ord ( "$0" ) ) ;';
            $sLine = preg_replace($sRegExp, $sReplmt, $sLine);
            
            // encode x09,x20 at lineends
            {
                $iLength = strlen($sLine);
                $iLastChar = ord($sLine {$iLength - 1} );
                
                //              !!!!!!!!
                // imap_8_bit does not encode x20 at the very end of a text,
                // here is, where I don't agree with imap_8_bit,
                // please correct me, if I'm wrong,
                // or comment next line for RFC2045 conformance, if you like
                if (!($bEmulate_imap_8bit && ($i == count($aLines) - 1)))
                
                    if (($iLastChar == 0x09) || ($iLastChar == 0x20))
                    {
                        $sLine {$iLength - 1} = '=';
                        $sLine .= ($iLastChar == 0x09) ? '09' : '20';
                    }
            } // imap_8bit encodes x20 before chr(13), too
            // although IMHO not requested by RFC2045, why not do it safer :)
            // and why not encode any x20 around chr(10) or chr(13)
            if ($bEmulate_imap_8bit)
            {
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
            $sLine = implode('='.chr(13).chr(10), $aMatch[0]); // add soft crlf's
        }
        
        // join lines into text
        return implode(chr(13).chr(10), $aLines);
    }
}
?>
