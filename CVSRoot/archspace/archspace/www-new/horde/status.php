<?php
/*
 * $Horde: horde/status.php,v 1.2.2.4 2003/01/03 12:57:45 jan Exp $
 *
 * Copyright 1999-2003 Charles J. Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

if (isset($hordeMessageStack) && is_array($hordeMessageStack)) {
    echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td class="item"><table border="0" cellspacing="2" cellpadding="2" width="100%">';
    foreach ($hordeMessageStack as $message) {
        switch ($message['type']) {
        case HORDE_ERROR:
            echo '<tr><td class="control">' . Horde::img('alerts/error.gif', 'alt="' . _("Error") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . $message['message'] . '</b></td></tr>';
            break;

        case HORDE_SUCCESS:
            echo '<tr><td class="control">' . Horde::img('alerts/success.gif', 'alt="' . _("Success") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . $message['message'] . '</b></td></tr>';
            break;

        case HORDE_WARNING:
            echo '<tr><td class="control">' . Horde::img('alerts/warning.gif', 'alt="' . _("Warning") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . $message['message'] . '</b></td></tr>';
            break;

        case HORDE_MESSAGE:
        default:
            echo '<tr><td class="control">' . Horde::img('alerts/message.gif', 'alt="' . _("Message") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . $message['message'] . '</b></td></tr>';
            break;

        }
    }
    echo '</table></td></tr></table><br />';
}
?>
