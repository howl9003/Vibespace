<?php
/*
 * $Horde: horde/lib/MIME/Viewer/tgz.php,v 1.10.2.6 2003/01/03 12:48:28 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_tgz class renders out plain or gzipped tarballs in
 * HTML format by executing tar in query mode.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_tgz extends MIME_Viewer {

    /**
     * Render out the currently set contents using Tar. The $mime_part
     * class variable has the information to render out, encapsulated
     * in a MIME_Part object.
     *
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['tgz']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['tgz']['location']) . '</pre>';
        }

        $tmp_tgz = Horde::getTempFile('hordetgz');

        $fh = fopen($tmp_tgz, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        if (in_array($this->mime_part->getType(), array('x-extension/tgz', 'application/x-gzip-compressed', 'application/x-gtar'))) {
            $options = 'tvzf';
        } else {
            $options = 'tvf';
        }
        $pipe = popen($mime_drivers['horde']['tgz']['location'] . " $options $tmp_tgz 2>&1", 'r');
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
