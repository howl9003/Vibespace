<?php
/*
 * $Horde: horde/lib/MIME/Viewer/msword.php,v 1.9.2.5 2003/01/03 12:48:27 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_msword class renders out Microsoft Word
 * documents in HTML format by using the wvWare package.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_msword extends MIME_Viewer {

    /**
     * Render out the currently set contents using wvWare.  The
     * $mime_part class variable has the information to render out,
     * encapsulated in a MIME_Part object.
     *
     * @return string HTML pretty-print of the content
     */
    function render($params = null)
    {
        global $mime_drivers;

        /* Check to make sure the program actually exists. */
        if (file_exists($mime_drivers['horde']['msword']['location']) === false) {
            return '<pre>' . sprintf(_("The program used to view this message type (%s) was not found on the system."), $mime_drivers['horde']['msword']['location']) . '</pre>';
        }

        $tmp_word   = Horde::getTempFile('msword');
        $tmp_output = Horde::getTempFile('msword');
        $tmp_dir    = Horde::getTempDir();
        $tmp_file   = str_replace($tmp_dir . '/', '', $tmp_output);

        $version = explode('.', exec($mime_drivers['horde']['msword']['location'] . ' --version'));
        if (count($version) > 2 && ($version[0] > 0 || $version[1] >= 7)) {
            $args = " --targetdir=$tmp_dir $tmp_word $tmp_file";
        } else {
            $args = " $tmp_word $tmp_output";
        }

        $fh = fopen($tmp_word, 'w');
        fwrite($fh, $this->mime_part->getContents());
        fclose($fh);

        exec($mime_drivers['horde']['msword']['location'] . $args);

        if (!file_exists($tmp_output)) {
            return _("Unable to translate this Word document");
        }

        $out = fopen($tmp_output, 'r');
        $data = '';
        while (($rc = fgets($out, 8192))) {
            $data .= $rc;
        }

        return $data;
    }

    function getType()
    {
        return 'text/html';
    }

}
