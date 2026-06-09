<?php
/*
 * $Horde: horde/lib/MIME.php,v 1.64.2.7 2003/01/14 23:38:36 slusarz Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

$mime_types =
array(
      TYPETEXT => 'text', 'text' => TYPETEXT,
      TYPEMULTIPART => 'multipart', 'multipart' => TYPEMULTIPART,
      TYPEMESSAGE => 'message', 'message' => TYPEMESSAGE,
      TYPEAPPLICATION => 'application', 'application' => TYPEAPPLICATION,
      TYPEAUDIO => 'audio', 'audio' => TYPEAUDIO,
      TYPEIMAGE => 'image', 'image' => TYPEIMAGE,
      TYPEVIDEO => 'video', 'video' => TYPEVIDEO,
      TYPEOTHER => 'unknown', 'unknown' => TYPEOTHER
      );

$mime_encodings =
array(
      ENC7BIT => '7bit', '7bit' => ENC7BIT,
      ENC8BIT => '8bit', '8bit' => ENC8BIT,
      ENCBINARY => 'binary', 'binary' => ENCBINARY,
      ENCBASE64 => 'base64', 'base64' => ENCBASE64,
      ENCQUOTEDPRINTABLE => 'quoted-printable', 'quoted-printable' => ENCQUOTEDPRINTABLE,
      ENCOTHER => 'unknown', 'unknown' => ENCOTHER
      );


/**
 * The MIME:: class provides methods for dealing with MIME standards.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime
 */
class MIME {

    /**
     * Determine if a string contains 8-bit characters.
     *
     * @access public
     *
     * @param string $string  The string to check.
     *
     * @return boolean  True if string contains 8-bit characters.
     */
    function is8bit($string)
    {
        if (is_string($string) && preg_match('/[\x80-\xff]+/', $string)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Encode a string containing non-ASCII characters according to RFC 2047.
     *
     * @access public
     *
     * @param string $text     The text to encode.
     * @param string $charset  The character set of the text.
     *
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    function encode($text, $charset)
    {
        /* Return if nothing needs to be encoded. */
        if (!MIME::is8bit($text)) {
            return $text;
        }

        $charset = strtolower($charset);
        $line = ''; 

        /* Get the list of elements in the string. */
        $size = preg_match_all("/([^\s]+)([\s]*)/", $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $key => $val) {
            if (MIME::is8bit($val[1])) {
                if ((($key + 1) < $size) &&
                    MIME::is8bit($matches[$key + 1][1])) {
                    $line .= MIME::_encode($val[1] . $val[2], $charset) . ' ';
                } else {
                    $line .= MIME::_encode($val[1], $charset) . $val[2];
                }
            } else {
                $line .= $val[1] . $val[2];
            }
        }

        return rtrim($line);
    }

    /**
     * Internal recursive function to RFC 2047 encode a string.
     *
     * @access private
     *
     * @param string $text     See MIME::encode().
     * @param string $charset  See MIME::encode().
     *
     * @return string  See MIME::encode().
     */
    function _encode($text, $charset)
    {
        $char_len = strlen($charset);
        $txt_len = strlen($text) * 2;

        /* RFC 2047 [2] states that no encoded word can be more than 75
           characters long. If longer, you must split the word. */
        if (($txt_len + $char_len + 7) > 75) {
            $pos = intval((68 - $char_len) / 2);
            return MIME::_encode(substr($text, 0, $pos), $charset) . ' ' . MIME::_encode(substr($text, $pos), $charset);
        } else {
            return '=?' . $charset . '?b?' . trim(base64_encode($text)) . '?=';
        }
    }

    /**
     * Encode a string containing email addresses according to RFC 2047.
     *
     * This differs from MIME::encode() because it keeps email
     * addresses legal, only encoding the personal information.
     *
     * @param string $text      The email addresses to encode.
     * @param string $charset   (optional) The character set of the text.
     * @param string $defserver (optional) The default domain to append to mailboxes.
     *
     * @return string The text, encoded only if it contains non-ascii characters.
     */
    function encodeAddress($text, $charset = null, $defserver = null)
    {
        include_once 'Mail/RFC822.php';

        $addr_arr = Mail_RFC822::parseAddressList($text, $defserver, false, false);
        $text = '';
        if (is_array($addr_arr)) {
            foreach ($addr_arr as $addr) {
                if (empty($addr->personal)) {
                    $personal = '';
                } else {
                    if ((substr($addr->personal, 0, 1) == '"') &&
                        (substr($addr->personal, -1) == '"')) {
                        $addr->personal = substr($addr->personal, 1, -1);
                    }
                    $personal = MIME::encode($addr->personal, $charset);
                }
                if (strlen($text) != 0) $text .= ', ';
                // FIXME: dependency on imap module
                $text .= MIME::trimEmailAddress(imap_rfc822_write_address($addr->mailbox, $addr->host, $personal));
            }
        }
        return $text;
    }

    /**
     * Decode an RFC 2047-encoded string.
     *
     * @param string $string The text to decode.
     * @return string        The decoded text, or the original string if it was not encoded.
     */
    function decode($string)
    {
        $pos = strpos($string, '=?');
        if ($pos === false) {
            return $string;
        }

        // take out any spaces between multiple encoded words
        $string = preg_replace('|\?=\s=\?|', '?==?', $string);

        $preceding = substr($string, 0, $pos); // save any preceding text

        $search = substr($string, $pos + 2);
        $d1 = strpos($search, '?');
        if (!is_int($d1)) {
            return $string;
        }

        $charset = substr($string, $pos + 2, $d1);
        $search = substr($search, $d1 + 1);

        $d2 = strpos($search, '?');
        if (!is_int($d2)) {
            return $string;
        }

        $encoding = substr($search, 0, $d2);
        $search = substr($search, $d2+1);

        $end = strpos($search, '?=');
        if (!is_int($end)) {
            return $string;
        }

        $encoded_text = substr($search, 0, $end);
        $rest = substr($string, (strlen($preceding . $charset . $encoding . $encoded_text) + 6));

        switch ($encoding) {
        case 'Q':
        case 'q':
            $encoded_text = str_replace('_', '%20', $encoded_text);
            $encoded_text = str_replace('=', '%', $encoded_text);
            $decoded = urldecode($encoded_text);

            /* Convert Cyrillic character sets. */
            if (stristr(Lang::getCharset(), 'windows-1251')) {
                if (stristr($charset, 'koi8-r')) {
                    $decoded = convert_cyr_string($decoded, 'k', 'w');
                }
            }
            if (stristr(Lang::getCharset(), 'koi8-r')) {
                if (stristr($charset, 'windows-1251')) {
                    $decoded = convert_cyr_string($decoded, 'w', 'k');
                }
            }

            break;

        case 'B':
        case 'b':
            $decoded = urldecode(base64_decode($encoded_text));
            if (stristr(Lang::getCharset(), 'windows-1251')) {
                if (stristr($charset, 'koi8-r')) {
                    $decoded = convert_cyr_string($decoded, 'k', 'w');
                }
            }
            if (stristr(Lang::getCharset(), 'koi8-r')) {
                if (stristr($charset, 'windows-1251')) {
                    $decoded = convert_cyr_string($decoded, 'w', 'k');
                }
            }
            break;

        default:
            $decoded = '=?' . $charset . '?' . $encoding . '?' . $encoded_text . '?=';
            break;
        }

        return $preceding . $decoded . MIME::decode($rest);
    }

    /**
     * If an email address has no personal information, get rid of any
     * angle brackets (<>) around it.
     *
     * @param string $address   The address to trim.
     * @return string           The trimmed address.
     */
    function trimEmailAddress($address)
    {
        $address = trim($address);
        if ((substr($address, 0, 1) == '<') && (substr($address, -1) == '>')) {
            $address = substr($address, 1, -1);
        }
        return $address;
    }

}
?>
