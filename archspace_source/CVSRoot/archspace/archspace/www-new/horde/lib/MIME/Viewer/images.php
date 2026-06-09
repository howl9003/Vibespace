<?php
/*
 * $Horde: horde/lib/MIME/Viewer/images.php,v 1.1.2.4 2003/01/03 12:48:27 jan Exp $
 *
 * Copyright 2002-2003 Michael Slusarz <slusarz@bigworm.colorado.edu>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_images class allows images to be displayed.
 *
 * @author  Michael Slusarz <slusarz@bigworm.colorado.edu>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.mime.viewer
 */

class MIME_Viewer_images extends MIME_Viewer {

    /**
     * Render out the currently set contents.
     * The $mime_part class variable has the information to render
     * out, encapsulated in a MIME_Part object.
     */
    function render(&$mime)
    {
        return $this->mime_part->getContents();
    }

    /**
     * Return the content-type
     *
     * @return string  The content-type of the output.
     */
    function getType()
    {
        return $this->mime_part->getType();
    }

}
