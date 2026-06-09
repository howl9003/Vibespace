<?php
/*
 * $Horde: horde/lib/MIME/Viewer/zip.php,v 1.1.2.6 2003/01/03 12:48:28 jan Exp $
 *
 * Copyright 2000-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_zip class renders out .zip files in HTML format by
 * executing zipinfo in query mode.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.0
 * @package horde.mime.viewer
 */
class MIME_Viewer_zip extends MIME_Viewer {

    /**
     * Render out the currently set contents using zipinfo. The
     * $mime_part class variable has the information to render out,
     * encapsulated in a MIME_Part object.
     *
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['zip']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['zip']['location']) . '</pre>';
        }

        $tmp_zip = Horde::getTempFile('hordezip');

        $fh = fopen($tmp_zip, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $pipe = popen($mime_drivers['horde']['zip']['location'] . " -m $tmp_zip 2>&1", 'r');
        $data = '<b>' . sprintf(_("Contents of '%s'"), $this->mime_part->name);
        $data .= '</b><br /><table><tr><td align="left"><pre>';

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
?>
