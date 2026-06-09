<?php
/*
 * $Horde: horde/lib/MIME/Viewer/rar.php,v 1.4.2.4 2003/01/03 12:48:26 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_rar class renders out .rar archives
 * in HTML format by executing rar in query mode.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_rar extends MIME_Viewer {

    /**
     * Render out the currently set contents using rar.  The
     * $mime_part class variable has the information to render out,
     * encapsulated in a MIME_Part object.
     *
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['rar']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['rar']['location']) . '</pre>';
        }

        $tmp_rar = Horde::getTempFile('horderar');

        $fh = fopen($tmp_rar, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $pipe = popen($mime_drivers['horde']['rar']['location'] . " l $tmp_rar 2>&1", 'r');
        $data = '<b><u>';
        $data .= _("Contents of the RAR archive");
        $data .= '</u></b><br /><table><tr><td align="left"><pre>';

        $re = '';
        while (($rc = fgets($pipe, 8192))) {
            $re .= $rc;
        }

        pclose($pipe);

        $data .= htmlspecialchars($re);
        $data .= '</pre></td></tr></table>';

        return $data;
    }

    function getType()
    {
        return 'text/html';
    }

}
