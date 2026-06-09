<?php
/*
 * $Horde: horde/lib/MIME/Viewer/rpm.php,v 1.1.2.5 2003/01/03 12:48:28 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_rpm class renders out lists of files in RPM
 * packages by using the rpm tool to query the package.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_rpm extends MIME_Viewer {

    /**
     * The $mime_part class variable has the information to render
     * out, encapsulated in a MIME_Part object.
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['rpm']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['rpm']['location']) . '</pre>';
        }

        $tmp_rpm = Horde::getTempFile('horde_rpm');

        $fh = fopen($tmp_rpm, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $pipe = popen($mime_drivers['horde']['rpm']['location'] . " -qip $tmp_rpm 2>&1", 'r');
        $data = '';
        while (($rc = fgets($pipe, 8192))) {
            $data .= $rc;
        }

        pclose($pipe);
        unlink($tmp_rpm);

        return '<pre>' . htmlentities($data) . '</pre>';
    }

    function getType()
    {
        return 'text/html';
    }

}
