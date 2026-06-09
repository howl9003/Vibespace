<?php
/*
 * $Horde: horde/lib/MIME/Viewer/enscript.php,v 1.7.2.8 2003/01/03 12:48:27 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_enscript class renders out various content
 * in HTML format by using GNU Enscript.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_enscript extends MIME_Viewer {

    /**
     * Render out the currently set contents using Enscript
     * The $mime_part class variable has the information to render
     * out, encapsulated in a MIME_Part object.
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['enscript']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['enscript']['location']) . '</pre>';
        }

        /* Create temporary files for both input and output to
         * Enscript. Note that we cannot use a pipe, since enscript
         * must have access to the whole file to determine its type
         * for coloured syntax highlighting. */
        $tmpin  = Horde::getTempFile('EnscriptIn');
        $tmpout = Horde::getTempFile('EnscriptOut');

        /* Write the contents of our buffer to the temporary input file */
        $fout = fopen($tmpin, 'wb');
        $contents = $this->mime_part->getContents();
        fwrite($fout, $contents, strlen($contents));
        fclose($fout);

        /* Execute the enscript command */
        include_once HORDE_BASE . '/lib/MIME/Magic.php';
        $lang = escapeshellarg($this->extensionToLang(MIME_Magic::MIMEToExt($this->mime_part->getType())));
        exec($mime_drivers['horde']['enscript']['location'] . " -E$lang --language=html --color --output=$tmpout < $tmpin");
        $results = implode('', file($tmpout));
        /* Strip out the extraneous HTML from Enscript, and output it.
         * TODO: figure out why this doesn't work with preg_replace! - avsm */
        $res_arr = preg_split('/\<\/?PRE\>/', $results);
        if (sizeof($res_arr) == 3) {
            return '<pre>' . $res_arr[1] . '</pre>';
        }
    }

    function getType()
    {
        return 'text/html';
    }

    function extensionToLang($ext)
    {
        switch ($ext) {
        case 'el':
            return 'elisp';

        case 'h':
            return 'c';

        case 'htm':
        case 'shtml':
        case 'xml':
            return 'html';

        case 'js':
            return 'javascript';

        case 'pas':
            return 'pascal';

        case 'pl':
        case 'pm':
            return 'perl';

        case 'ps':
            return 'postscript';

        case 'vb':
            return 'vba';

        case 'vhd':
            return 'vhdl';

        default:
            return $ext;
        }
    }

}
?>
