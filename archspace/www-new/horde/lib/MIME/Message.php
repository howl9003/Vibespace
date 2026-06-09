<?php
/*
 * $Horde: horde/lib/MIME/Message.php,v 1.9.2.8 2003/01/03 12:48:25 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once dirname(__FILE__) . '/Part.php';

/**
 * The MIME_Message:: class provides methods for creating MIME email
 * messages.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime
 */
class MIME_Message {

    /** The string that separates MIME parts from each other inside the message.
        @var string $separator */
    var $separator;

    /** The array of MIME parts in the message.
        @var array $parts */
    var $parts;

    /** The server to default unqualified addresses to.
        @var string $defaultServer */
    var $defaultServer;


    /**
     * Create a new MIME email message.
     *
     * @param string $defaultServer (optional) The server to default unqualified addresses to.
     * @return object MIME_Message  The new object
     */
    function MIME_Message($defaultServer = null)
    {
        $this->parts = array();
        $this->separator = '-MOQ' . (string)date('U') . md5(uniqid(rand()));
        if (!isset($defaultServer)) {
            $this->defaultServer = $_SERVER['SERVER_NAME'];
        } else {
            $this->defaultServer = $defaultServer;
        }
    }


    /**
     * Add a MIME_Part object to this message.
     *
     * @param object MIME_Part $part The part to add.
     */
    function addPart($part)
    {
        $this->parts[] = $part;
    }

    /**
     * Take a set of headers and make sure they are encoded properly.
     *
     * @param array $headers  The headers to encode.
     * @return array          The array of encoded headers.
     */
    function encode($headers)
    {
        if (empty($charset)) {
            $charset = Lang::getCharset();
        }

        foreach ($headers as $key => $val) {
            if ($key == 'To' || $key == 'Cc' || $key == 'Bcc' || $key == 'From') {
                $headers[$key] = MIME::encodeAddress($val, $charset, $this->defaultServer);
            } else {
                $headers[$key] = MIME::encode($val, $charset);
            }
        }
        return $headers;
    }

    /**
     * Add the proper set of MIME headers for this message to an array.
     *
     * @param array $headers  The headers to add the MIME headers to.
     * @return array          The full set of headers including MIME headers.
     */
    function header($headers = array())
    {
        $headers['MIME-Version'] = '1.0';
        $headers = $this->encode($headers);
        if (count($this->parts) > 1) {
            $headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->separator . '"';
        } elseif (count($this->parts) > 0) {
            $headers = $this->parts[0]->header($headers);
        }
        return $headers;
    }

    /**
     * Return the entire message contents, including headers, as a string.
     *
     * @return string The encoded, generated message.
     */
    function toString()
    {
        $message = '';
        if (count($this->parts) == 1) {
            /* Only one part, no need to make a multipart message */
            $part = $this->parts[0];
            $message = $part->getContents();
        } else {
            /* Output the multipart MIME message */
            $message = "This message is in MIME format.\n";
            foreach ($this->parts as $part) {
                $message .= "\n--$this->separator\n";
                $message .= $part->toString();
            }
            $message .= "\n--$this->separator--\n";
        }

        return $message;
    }

}
?>
