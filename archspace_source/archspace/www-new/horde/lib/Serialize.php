<?php
/*
 * $Horde: horde/lib/Serialize.php,v 1.1.2.9 2003/01/17 10:22:15 jan Exp $
 *
 * Copyright 1999-2003 Stephane Huther <shuther@bigfoot.com>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

// Return codes
/** @constant SERIALIZEUNIT_OK Operation succeeded. */
define('SERIALIZEUNIT_OK', 0);

/** @constant SERIALIZEUNIT_ERROR Operation failed. */
define('SERIALIZEUNIT_ERROR', -1);

/** @constant SERIALIZEUNIT_ERROR_PARAMS Bad or missing parameters. */
define('SERIALIZEUNIT_ERROR_PARAMS', -2);

/** @constant SERIALIZEUNIT_ERROR_CONNECT Connection failure. */
define('SERIALIZEUNIT_ERROR_CONNECT', -3);

/** @constant SERIALIZEUNIT_ERROR_AUTH Authentication failure. */
define('SERIALIZEUNIT_ERROR_AUTH', -4);

/** @constant SERIALIZEUNIT_ERROR_EMPTY Empty retrieval result. */
define('SERIALIZEUNIT_ERROR_EMPTY', -5);

/** @constant SERIALIZEUNIT_ERROR_UNSUPPORTED Method not supported by
    driver. */
define('SERIALIZEUNIT_ERROR_UNSUPPORTED', -6);

// Kinds of serialization
/** @constant SERIALIZEUNIT_NONE no serialization */
define('SERIALIZEUNIT_NONE', 0);

/** @constant SERIALIZEUNIT_WDDX use WDDX */
define('SERIALIZEUNIT_WDDX', 1);

/** @constant SERIALIZEUNIT_BZIP use BZIP */
define('SERIALIZEUNIT_BZIP', 2);

/** @constant SERIALIZEUNIT_IMAP8 use IMAP 8b */
define('SERIALIZEUNIT_IMAP8', 3);

/** @constant SERIALIZEUNIT_IMAPUTF7 use IMAP UTF 7 */
define('SERIALIZEUNIT_IMAPUTF7', 4);

/** @constant SERIALIZEUNIT_IMAPUTF8 use IMAP UTF 8 */
define('SERIALIZEUNIT_IMAPUTF8', 5);

/** @constant SERIALIZEUNIT_BASIC use the basic method serialize from
    PHP */
define('SERIALIZEUNIT_BASIC', 6);

/** @constant SERIALIZEUNIT_GZ_DEFLAT GZ with deflate */
define('SERIALIZEUNIT_GZ_DEFLAT', 7);

/** @constant SERIALIZEUNIT_GZ_COMPRESS GZ with compress */
define('SERIALIZEUNIT_GZ_COMPRESS', 8);

/** @constant SERIALIZEUNIT_GZ_ENCOD GZ with encode */
define('SERIALIZEUNIT_GZ_ENCOD', 9);

/** @constant SERIALIZEUNIT_BASE64 Use Base64 */
define('SERIALIZEUNIT_BASE64', 10);

/** @constant SERIALIZEUNIT_SQLXML Use Sql2XML */
define('SERIALIZEUNIT_SQLXML', 11);

/** @constant SERIALIZEUNIT_RAW Raw url encode*/
define('SERIALIZEUNIT_RAW', 12);

/** @constant SERIALIZEUNIT_URL Url encode*/
define('SERIALIZEUNIT_URL', 13);

/** @constant SERIALIZEUNIT_UNKNOWN the mode is unknown */
define('SERIALIZEUNIT_UNKNOWN', -1);

// Specific default values
// BZIP
/** @constant SERIALIZEUNIT_BZIP_BLOCK define the block size 1-9 (the
    best) */
define('SERIALIZEUNIT_BZIP_BLOCK', 9);

/** @constant SERIALIZEUNIT_BZIP_FACTOR work factor - not used */
define('SERIALIZEUNIT_BZIP_FACTOR', 30);

/** @constant SERIALIZEUNIT_BZIP_SMALLMEM Try to minimize the load of
    memmory*/
define('SERIALIZEUNIT_BZIP_SMALLMEM', false);

// GZ
/** @constant SERIALIZEUNIT_GZ_DEFLAT_LEVEL level of compression -
    deflate 1-9(the best) */
define('SERIALIZEUNIT_GZ_DEFLAT_LEVEL', 9);

/** @constant SERIALIZEUNIT_GZ_DEFLAT_LENGTH max length of the
    uncompressed string*/
define('SERIALIZEUNIT_GZ_DEFLAT_LENGTH', 2048);

/** @constant SERIALIZEUNIT_GZ_COMPRESS_LEVEL level of compression -
    compress 1-9(the best) */
define('SERIALIZEUNIT_GZ_COMPRESS_LEVEL', 9);

/** @constant SERIALIZEUNIT_GZ_COMPRESS_LENGTH max length of the
    uncompressed string*/
define('SERIALIZEUNIT_GZ_COMPRESS_LENGTH', 2048);

/** @constant SERIALIZEUNIT_GZ_ENCOD_LEVEL level of compression -
    encode 1-9(the best) */
define('SERIALIZEUNIT_GZ_ENCOD_LEVEL', 9);

/**
 * Array (Kind of serialization => 2nd serialization)
 * It is possible to chain several serialization one after the other one
 * If a key is not found, the working process will stop, usefull
 * for SERIALIZEUNIT_NONE
 */
$__SERIALIZATION_CHAIN = array();

/**
 * This is a tool class, every method is static
 *
 * @author  Stephane Huther shuther@bigfoot.com
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.0
 * @package horde
 */
class SerializeUnit {

    /**
     * Init $__SERIALIZATION_CHAIN to work properly with SerializeUnit after
     * @param array $insert content a parameter array that will be merged
     * @note for future compatibility raison, it is important to call this method
     */
    function init($insert=array())
    {
        global $__SERIALIZATION_CHAIN;

        if (!empty($insert))
        {
            $first=array();
            if (SerializeUnit::capability(SERIALIZEUNIT_GZ_COMPRESS)) {
                $first[SERIALIZEUNIT_WDDX]=SERIALIZEUNIT_GZ_COMPRESS;
                // $first[SERIALIZEUNIT_BASIC]=SERIALIZEUNIT_GZ_COMPRESS;
            }
            elseif (SerializeUnit::capability(SERIALIZEUNIT_BZIP)) {
                $first[SERIALIZEUNIT_WDDX]=SERIALIZEUNIT_BZIP;
                // $first[SERIALIZEUNIT_BASIC]=SERIALIZEUNIT_BZIP;
            }
            $__SERIALIZATION_CHAIN = array_merge($__SERIALIZATION_CHAIN, $first);
        }

        $__SERIALIZATION_CHAIN = array_merge($__SERIALIZATION_CHAIN,
                                              $insert);
    }

    /**
     * we serialize a value, it can be a string, an array (with the
     * method:SERIALIZEUNIT_WDDX), a DB::result (SERIALIZEUNIT_SQLXML)
     * or nearly everything (SERIALIZEUNIT_BASIC)
     *
     * @param mixed $var what are we going to serialize
     * @param integer $mode In which mode we will serialize, by default SERIALIZE
     * @return string the result or error (integer)
     */
    function &serializeUnit($var, $mode = 6)
    {
        global $__SERIALIZATION_CHAIN;

        switch ($mode) {
        case SERIALIZEUNIT_NONE:
            $out = $var;
            break;
        case SERIALIZEUNIT_BZIP:
            $out = bzcompress($var, SERIALIZEUNIT_BZIP_BLOCK, SERIALIZEUNIT_BZIP_FACTOR);
            break;
        case SERIALIZEUNIT_WDDX:
            $out = wddx_serialize_value($var);
            break;
        case SERIALIZEUNIT_IMAP8:
            $out = imap_8bit($var);
            break;
        case SERIALIZEUNIT_IMAPUTF7:
            $out = imap_utf7_encode($var);
            break;
        case SERIALIZEUNIT_IMAPUTF8:
            $out = imap_utf8($var);
            break;
        case SERIALIZEUNIT_GZ_DEFLAT:
            $out = gzdeflate($var, SERIALIZEUNIT_GZ_DEFLAT_LEVEL);
            break;
        case SERIALIZEUNIT_BASIC:
            $out = serialize($var);
            break;
        case SERIALIZEUNIT_GZ_COMPRESS:
            $out=gzcompress($var, SERIALIZEUNIT_GZ_COMPRESS_LEVEL);
            break;
        case SERIALIZEUNIT_BASE64:
            $out=base64_encode($var);
            break;
        case SERIALIZEUNIT_GZ_ENCOD:
            $out=gzencode($var, SERIALIZEUNIT_GZ_ENCOD_LEVEL);
            break;
        case SERIALIZEUNIT_RAW:
            $out=rawurlencode($var);
            break;
        case SERIALIZEUNIT_URL:
            $out=urlencode($var);
            break;
        case SERIALIZEUNIT_SQLXML:
            include_once ('DB.php');
            include_once('XML/sql2xml.php');
            $sql2xml = new xml_sql2xml();
            $out = $sql2xml->getXML($var);
            break;

        default:
            return SERIALIZEUNIT_ERROR_UNSUPPORTED;
        }

        if (isset($__SERIALIZATION_CHAIN[$mode]))
            return SerializeUnit::serializeUnit($out, $__SERIALIZATION_CHAIN[$mode]);
        return $out;
    }

    /*
     * we unserialize a value
     * @param mixed $var what are we going to unserialize
     * @param integer $mode In which mode we will unserialize, by default, the mode
     *                      is unknown, so we will detect it - not supported by now!
     * @return string the result
     */
    function &unSerializeUnit($var, $mode = 6)
    {
        global $__SERIALIZATION_CHAIN;

        if (empty($var)) {
            return $var;
        }
        if (isset($__SERIALIZATION_CHAIN[$mode])) {
            $var = SerializeUnit::deSerializeUnit($var, $__SERIALIZATION_CHAIN[$mode]);
        }

        switch ($mode) {
        case SERIALIZEUNIT_SQLXML:
        case SERIALIZEUNIT_NONE:
            $out = $var;
            break;
        case SERIALIZEUNIT_RAW:
            $out = rawurldecode($var);
            break;
        case SERIALIZEUNIT_URL:
            $out = urldecode($var);
            break;
        case SERIALIZEUNIT_WDDX:
            $out = wddx_deserialize($var);
            break;
        case SERIALIZEUNIT_BZIP:
            $out = bzdecompress($var, SERIALIZEUNIT_BZIP_SMALLMEM);
            break;
        case SERIALIZEUNIT_IMAPUTF7:
            $out = imap_utf7_decode($var);
            break;
        case SERIALIZEUNIT_BASIC:
            $out = unserialize($var);
            break;
        case SERIALIZEUNIT_GZ_DEFLAT:
            $out = gzinflate($var, SERIALIZEUNIT_GZ_DEFLAT_LENGTH);
            break;
        case SERIALIZEUNIT_BASE64:
            $out = base64_decode($var);
            break;
        case SERIALIZEUNIT_GZ_COMPRESS:
            $out = gzuncompress($var, SERIALIZEUNIT_GZ_COMPRESS_LENGTH);
            break;
        case SERIALIZEUNIT_UNKNOWN:
            $a = SerializeUnit::deSerializeUnit($strin, SERIALIZEUNIT_WDDX);
            return is_null($a) ? $a : SerializeUnit::deSerializeUnit($strin, SERIALIZEUNIT_NONE);
            break;

        default:
            return SERIALIZEUNIT_ERROR_UNSUPPORTED;
        }

        return $out;

    }

    /**
     * Check that a serialize system is supported or not
     * @param integer $mode Kind of serialization
     * @return boolean true of false
     */
    function capability($mode)
    {
        switch ($mode) {
        case SERIALIZEUNIT_BZIP:
            return extension_loaded('bz2');

        case SERIALIZEUNIT_WDDX:
            return extension_loaded('wddx');

        case SERIALIZEUNIT_IMAPUTF7:
        case SERIALIZEUNIT_IMAPUTF8:
        case SERIALIZEUNIT_IMAP8:
            return extension_loaded('imap');

        case SERIALIZEUNIT_GZ_DEFLAT:
        case SERIALIZEUNIT_GZ_COMPRESS:
        case SERIALIZEUNIT_GZ_ENCOD:
            return extension_loaded('zlib');

        case SERIALIZEUNIT_NONE:
        case SERIALIZEUNIT_BASIC:
        case SERIALIZEUNIT_BASE64:
        case SERIALIZEUNIT_SQLXML:
        case SERIALIZEUNIT_RAW:
        case SERIALIZEUNIT_URL:
            return true;

        default:
            return false;
        }
    }

}
?>
