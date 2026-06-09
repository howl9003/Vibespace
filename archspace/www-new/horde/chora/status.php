<?php
/**
 * $Horde: chora/status.php,v 1.1.2.5 2004/03/26 22:43:22 jan Exp $
 *
 * Copyright 1999-2004 Charles J. Hagenbuch <chuck@horde.org>
 * Copyright 1999-2004 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

if ($browser->hasFeature('html') &&
    isset($hordeMessageStack) &&
    is_array($hordeMessageStack)) {
    echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td class="item"><table border="0" cellspacing="2" cellpadding="2" width="100%">';
    foreach ($hordeMessageStack as $message) {
        switch ($message['type']) {
        case HORDE_ERROR:
            echo '<tr><td class="control">' . Horde::img('alerts/error.gif', 'alt="' . _("Error") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        case HORDE_SUCCESS:
            echo '<tr><td class="control">' . Horde::img('alerts/success.gif', 'alt="' . _("Success") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        case HORDE_WARNING:
            echo '<tr><td class="control">' . Horde::img('alerts/warning.gif', 'alt="' . _("Warning") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        case HORDE_MESSAGE:
        default:
            echo '<tr><td class="control">' . Horde::img('alerts/message.gif', 'alt="' . _("Message") . '" class="control"', $registry->getGraphicsPath("horde")) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        }
    }
    echo '</table></td></tr></table><br />';
}
