<?php
// $Horde: chora/lib/MIME/Viewer/text.php,v 1.2.2.1 2003/01/03 13:23:30 jan Exp $

/**
 * The Chora_MIME_Viewer_text class renders out plain text with
 * URLs made into hyperlinks.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.6.4
 * @package horde.mime.viewer
 */

class Chora_MIME_Viewer_text extends MIME_Viewer {

    /**
     * Render out the currently set contents in HTML format.
     * The $mime_part class variable has the information to render
     * out, encapsulated in a MIME_Part object.
     */
    function render() {
        return nl2br(trim(preg_replace('%(http|ftp)(://\S+)%', '<a href="\1\2">\1\2</a>', htmlspecialchars($this->mime_part->getContents()))));
    }

    /**
     * Return text/html as the content-type
     * @return string "text/html" constant
     */
    function getType() {
        return 'text/html';
    }
}
