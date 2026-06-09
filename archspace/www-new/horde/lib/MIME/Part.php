<?php
/*
 * $Horde: horde/lib/MIME/Part.php,v 1.11.2.9 2003/01/03 12:48:24 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Part:: class provides a wrapper around MIME parts and methods
 * for dealing with them.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime
 */
class MIME_Part {

    /** The type (ex.: text) of this part. */
    var $type = 'application';

    /** The subtype (ex.: plain) of this part. */
    var $subtype = 'octet-stream';

    /** The body of the part. */
    var $contents = '';

    /** The transfer encoding of this part. */
    var $transferEncoding = '7bit';

    /** The description of this part. */
    var $description = '';

    /** The name of this part. */
    var $name = '';

    /** The character set of this part. */
    var $charset = '';

    /** The disposition of this part (inline or attachment). */
    var $disposition = 'attachment';


    /**
     * MIME_Part constructor.
     *
     * @param string $mimetype  The mimetype (ex.: 'text/plain') of the part.
     * @param string $contents  The body of the part.
     *
     * @return object MIME_Part The new MIME_Part object.
     */
    function MIME_Part($mimetype = null, $contents = null, $charset = null, $disposition = null)
    {
        if (!is_null($mimetype)) {
            $this->setType($mimetype);
        }
        if (!is_null($contents)) {
            $this->contents = $contents;
        }
        if (!is_null($charset)) {
            $this->charset = $charset;
        }
        if (!is_null($disposition)) {
            $this->disposition = $disposition;
        }
    }


    /**
     * Set the mimetype of this part.
     *
     * @param string $mimetype The mimetype to set (ex.: text/plain).
     */
    function setType($mimetype)
    {
        list($this->type, $this->subtype) = explode('/', strtolower($mimetype));
    }

    /*
     * @since Horde 2.1
     */
    function setDisposition($disposition)
    {
        $this->disposition = $disposition;
    }

    /**
     * Set the name of this part.
     *
     * @param string $name The name to set.
     */
    function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the body of this part.
     *
     * @param string $contents The part body.
     */
    function setContents($contents)
    {
        $this->contents = $contents;
    }

    /**
     * Add to the body of this part.
     *
     * @param string $contents The content to append to the part body.
     */
    function appendContents($contents)
    {
        $this->contents .= $contents;
    }

    /**
     * Return the body of the part.
     *
     * @return string The body of the part.
     */
    function getContents()
    {
        return $this->contents;
    }

    /**
     * Get the full mimetype of this part
     *
     * @return string The mimetype of this part (ex.: text/plain)
     */
    function getType()
    {
        if (!isset($this->type) || !isset($this->subtype)) {
            return false;
        } else {
            return $this->type . '/' . $this->subtype;
        }
    }

    /**
     * Set the transfer encoding of the part.
     *
     * @param string $encoding The transfer-encoding to use.
     */
    function setTransferEncoding($encoding)
    {
        $this->transferEncoding = $encoding;
    }

    /**
     * Add the appropriate MIME headers for this part to an existing array.
     *
     * @param array $headers  An array of any other headers for the part.
     * @return array          The headers, with the MIME headers added.
     */
    function header($headers)
    {
        $headers['Content-Type'] = $this->getType();
        if (!empty($this->name))
            $headers['Content-Type'] .= '; name="' . $this->name . '"';

        $this->setEncoding();

        if (($this->type == 'text') && (!empty($this->charset))) {
            $headers['Content-Type'] .= '; charset=' . $this->charset;
        }
        $headers['Content-Transfer-Encoding'] = $this->transferEncoding;

        if ($this->name != '') {
            $headers['Content-Disposition'] = $this->disposition . '; filename="' . $this->name . '"';
        }
        if ($this->description != '') {
            $headers['Content-Description'] = $this->description;
        }

        return $headers;
    }

    /**
     * Return the entire part, including headers.
     *
     * @return string The entire MIME part.
     */
    function toString()
    {
        $part = '';
        $headers = $this->header(array());
        while (list($key, $val) = each($headers)) {
            $part .= $key . ': ' . $val . "\n";
        }

        return $part . "\n" . $this->getContents();
    }

    /**
     * Set the encoding of the message and the charset based on the
     * current language and browser capabilities.
     */
    function setEncoding()
    {
        $text = str_replace("\n", ' ', $this->contents);

        // FIXME: dependancy on IMAP module
        if ($this->type == 'text'
            && $this->transferEncoding != 'base64'
            && imap_8bit($text) != $text) {
            $this->transferEncoding = '8bit';

            global $language, $nls;

            if (empty($this->charset)) {
                if (!empty($nls['charsets'][$language])) {
                    $this->charset = $nls['charsets'][$language];
                } else if (!empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
                    $charsets = explode(',', $_SERVER['HTTP_ACCEPT_CHARSET']);
                    if (!empty($charsets[0])) {
                        $this->charset = trim($charsets[0]);
                    }
                }

                if (empty($this->charset)) {
                    $this->charset = Lang::getCharset();
                }
            }
        }
    }

}
?>
