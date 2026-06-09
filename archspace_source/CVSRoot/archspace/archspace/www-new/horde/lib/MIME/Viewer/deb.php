<?php
/*
 * $Horde: horde/lib/MIME/Viewer/deb.php,v 1.1.2.4 2003/01/03 12:48:26 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_deb class renders out lists of files in Debian
 * packages by using the dpkg tool to query the package.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_deb extends MIME_Viewer {

    /**
     * The $mime_part class variable has the information to render
     * out, encapsulated in a MIME_Part object.
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['deb']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['deb']['location']) . '</pre>';
        }

        $tmp_deb = Horde::getTempFile('horde_deb');

        $fh = fopen($tmp_deb, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $pipe = popen($mime_drivers['horde']['deb']['location'] . " -f $tmp_deb 2>&1", 'r');
        while (($rc = fgets($pipe, 8192))) {
            $data .= $rc;
        }

        pclose($pipe);
        unlink($tmp_deb);

        return '<pre>' . $htmlentities($data) . '</pre>';
    }

    function getType()
    {
        return 'text/html';
    }

}
