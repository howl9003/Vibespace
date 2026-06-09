<?php
// $Horde: horde/lib/Notification/status.php,v 1.7.2.7 2003/09/15 03:01:02 chuck Exp $

/**
 * The Notification_status:: class provides functionality for displaying
 * messages from the message stack as a status line.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.notification
 */
class Notification_status {

    /**
     * Return a unique identifier for this listener.
     *
     * @access public
     *
     * @return string  Unique id.
     */
    function getName()
    {
        return 'status';
    }

    /**
     * Outputs the status line if there are any messages on the
     * 'status' message stack.
     */
    function notify(&$messageStacks)
    {
        if (count($messageStacks['status'])) {
            echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td class="item"><table border="0" cellspacing="2" cellpadding="2" width="100%">';
            while ($message = array_shift($messageStacks['status'])) {
                $this->getMessage($message);
            }
            echo "</table></td></tr></table>\n<br />\n";
        }
    }

    /**
     * Outputs one message.
     *
     * @param array $message    One message hash from the stack.
     */
    function getMessage($message)
    {
        global $registry;

        switch ($message['type']) {
        case 'horde.error':
            echo '<tr><td class="control">' . Horde::img('alerts/error.gif', 'alt="' . _("Error") . '" class="control"', $registry->getParam('graphics', 'horde')) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        case 'horde.success':
            echo '<tr><td class="control">' . Horde::img('alerts/success.gif', 'alt="' . _("Success") . '" class="control"', $registry->getParam('graphics', 'horde')) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        case 'horde.warning':
            echo '<tr><td class="control">' . Horde::img('alerts/warning.gif', 'alt="' . _("Warning") . '" class="control"', $registry->getParam('graphics', 'horde')) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;

        case 'horde.message':
        default:
            echo '<tr><td class="control">' . Horde::img('alerts/message.gif', 'alt="' . _("Message") . '" class="control"', $registry->getParam('graphics', 'horde')) . '&nbsp;&nbsp;<b>' . htmlspecialchars($message['message']) . '</b></td></tr>';
            break;
        }
    }

}
