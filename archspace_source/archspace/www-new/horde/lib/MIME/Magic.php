<?php
/*
 * $Horde: horde/lib/MIME/Magic.php,v 1.3.2.9 2003/01/03 12:48:24 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Magic:: class provides an interface to determine a
 * MIME type for various content, if it provided with different
 * levels of information.
 *
 * Currently, it can map a file extension to a MIME type, but
 * future ideas include using Apache's mod_mime_magic (if available).
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime
 */
class MIME_Magic {

    /**
     * Returns a copy of the MIME extension map.
     *
     * @access private
     *
     * @return array  The MIME extension map.
     *
     * @since Horde 2.2
     */
    function _getMimeExtensionMap()
    {
        static $mime_extension_map;

        if (!isset($mime_extension_map)) {
            require HORDE_BASE . '/config/mime_mapping.php';
        }

        return $mime_extension_map;
    }

    /**
     * Attempt to convert a file extension to a MIME type, based
     * on the global Horde and application specific config files.
     *
     * If we cannot map the file extension to a specific type, then
     * we fall back to a custom MIME handler x-extension/type, which
     * can be used as a normal MIME type internally throughout Horde.
     *
     * @access public
     *
     * @param string $ext  The file extension to be mapped to a MIME type.
     *
     * @return string  The MIME type of the file extension.
     */
    function extToMIME($ext)
    {
        if (empty($ext)) {
           return 'text/plain';
        } else {
            $ext = strtolower($ext);
            $map = MIME_Magic::_getMimeExtensionMap();
            if (!array_key_exists($ext, $map)) {
                return "x-extension/$ext";
            } else {
                return $map[$ext];
            }
        }
    }

    /**
     * Attempt to convert a filename to a MIME type, based on the
     * global Horde and application specific config files.
     *
     * Unlike extToMIME(), this function will return
     * 'application/octet-stream' for any unknown or empty extension.
     *
     * @access public
     *
     * @param string $filename  The filename to be mapped to a MIME type.
     *
     * @return string  The MIME type of the filename.
     *
     * @since Horde 2.2
     */
    function filenameToMIME($filename)
    {
        $pos = strrpos($filename, '.');
        if (!empty($pos)) {
            $type = MIME_Magic::extToMIME(substr($filename, $pos + 1));
            if (!stristr($type, 'x-extension')) {
                return $type;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Attempt to convert a MIME type to a file extension, based
     * on the global Horde and application specific config files.
     *
     * If we cannot map the type to a file extension, we return false.
     *
     * @access public
     *
     * @param string $type The MIME type to be mapped to a file extension
     * @return string      The file extension of the MIME type
     *
     * @since Horde 2.1
     */
    function MIMEToExt($type)
    {
        $key = array_search($type, MIME_Magic::_getMimeExtensionMap());
        if (empty($type) || ($key === false) || ($key === null)) {
            list($major, $minor) = explode('/', $type);
            if ($major == 'x-extension') {
                return $minor;
            }
            return false;
        } else {
            return $key;
        }
    }

}
?>
