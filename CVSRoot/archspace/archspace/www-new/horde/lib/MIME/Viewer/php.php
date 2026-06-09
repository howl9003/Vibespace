<?php
/*
 * $Horde: horde/lib/MIME/Viewer/php.php,v 1.8.2.4 2003/01/03 12:48:28 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_php class renders out syntax-highlighted PHP
 * code in HTML format.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */
class MIME_Viewer_php extends MIME_Viewer {

    /**
     * Render out the currently set contents in HTML format.  The
     * $mime_part class variable has the information to render out,
     * encapsulated in a MIME_Part object.
     */
    function render($params = null)
    {
        ob_start();
        highlight_string($this->mime_part->getContents());
        $pretty = ob_get_contents();
        ob_end_clean();
        return $pretty;
    }

    /**
     * Return text/html as the content-type.
     *
     * @return string "text/html" constant
     */
    function getType()
    {
        return 'text/html';
    }

}
