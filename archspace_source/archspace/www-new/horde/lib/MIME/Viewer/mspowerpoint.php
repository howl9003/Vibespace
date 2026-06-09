<?php
/*
 * $Horde: horde/lib/MIME/Viewer/mspowerpoint.php,v 1.3.2.4 2003/01/03 12:48:27 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_mspowerpoint class renders out Microsoft Powerpoint
 * documents in HTML format by using the xlHtml package.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_mspowerpoint extends MIME_Viewer {

    /**
     * Render out the currently set contents using ppthtml.
     * The $mime_part class variable has the information to render
     * out, encapsulated in a MIME_Part object.
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['mspowerpoint']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['mspowerpoint']['location']) . '</pre>';
        }

        $tmp_ppt = Horde::getTempFile('horde_mspowerpoint');

        $fh = fopen($tmp_ppt, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        $data = '';
        $pipe = popen($mime_drivers['horde']['mspowerpoint']['location'] . " $tmp_ppt 2>&1", 'r');
        while (($rc = fgets($pipe, 8192))) {
            $data .= $rc;
        }

        pclose($pipe);
        unlink($tmp_ppt);

        return $data;
    }

    function getType()
    {
        return 'text/html';
    }

}
