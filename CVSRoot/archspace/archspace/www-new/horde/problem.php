<?php
/**
 * $Horde: horde/problem.php,v 2.62.2.13 2003/07/03 17:47:09 jan Exp $
 *
 * Copyright 1999-2003 Charles J. Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/* Send the browser back to the correct page. */
function returnToPage() {
    $returnURL = Horde::getFormData('return_url', 'login.php');
    header('Location: ' . str_replace('&amp;', '&', $returnURL));
}

define('HORDE_BASE', dirname(__FILE__));
require_once HORDE_BASE . '/lib/base.php';
require_once HORDE_BASE . '/lib/Identity.php';
require_once HORDE_BASE . '/lib/version.php';

if (!Auth::getAuth()) {
    returnToPage();
}

$identity = new Identity();
$name = Horde::getFormData('name', $identity->getValue('fullname'));
$email = Horde::getFormData('email', $identity->getValue('from_addr'));
$subject = Horde::getFormData('subject', '');
$message = Horde::getFormData('message', '');

/* Run through action handlers */
$actionID = Horde::getFormData('actionID');
switch ($actionID) {
 case HORDE_SEND_PROBLEM_REPORT:
     include_once HORDE_BASE . '/lib/Text.php';

     if (!empty($subject) && !empty($message)) {
         include_once HORDE_BASE . '/lib/MIME.php';
         include_once HORDE_BASE . '/lib/MIME/Message.php';

         // add a Received header for the hop from browser to server.
         $remote = (!empty($_SERVER['REMOTE_HOST'])) ? $_SERVER['REMOTE_HOST'] : $_SERVER['REMOTE_ADDR'];
         $user_agent = $_SERVER['HTTP_USER_AGENT'];
         $headers['Received'] = 'from ' . $remote . ' (';
         if (!empty($_SERVER['REMOTE_IDENT'])) $headers['Received'] .= $_SERVER['REMOTE_IDENT'] . '@' . $remote;
         $headers['Received'] .= ' [' . $remote . '])';
         $headers['Received'] .= "\n\t by " . $registry->getParam('server_name') . ' with HTTP;';
         $headers['Received'] .= "\n\t" . date('r');

         $headers['Message-ID'] = '<' . uniqid(time() . '.') . '@' . $registry->getParam('server_name') . '>';
         $headers['Date'] = date('r');
         $headers['To'] = $conf['problems']['email'];
         if (!empty($email)) {
             if (!empty($name)) {
                 // FIXME: need Mail_RFC822::writeAddress() here
                 $headers['From'] = '"' . addslashes($name) . '" <' . $email . '>';
             } else {
                 $headers['From'] = $email;
             }
             $headers['Sender'] = 'horde-problem@' . $registry->getParam('server_name');
         } else {
             $headers['From'] = 'horde-problem@' . $registry->getParam('server_name');
         }
         $recipients = $conf['problems']['email'];
         $headers['Subject'] = _("[Problem Report]") . ' ' . $subject;
         $headers['User-Agent'] = 'Horde ' . HORDE_VERSION;

         $message = str_replace("\r\n", "\n", $message);

         // This is not a gettext string on purpose.
         $message = "This problem report was received from $remote. " .
             "The user clicked the problem report link from the following location:\n" .
             Horde::getFormData('return_url', 'No requesting page') .
             "\nand is using the following browser:\n$user_agent\n\n$message";

         $mime = new MIME_Message();
         $body = new MIME_Part('text/plain', Text::wrap($message, 80, "\n"));

         $mime->addPart($body);
         $headers = $mime->header($headers);
         $msg = $mime->toString();

         include_once 'Mail.php';
         $mailer = &Mail::factory($conf['mailer']['type'], $conf['mailer']['params']);
         if (!PEAR::isError($mailer->send($recipients, $headers, $msg))) {
             /* We succeeded. Return to previous page and exit this script. */
             returnToPage();
             exit;
         } else {
             $label = _("Describe the Problem");
         }
     } else {
         /* Something wasn't quite right. Strange. */
         $label = _("Describe the Problem");
     }
     break;

 case HORDE_CANCEL_PROBLEM_REPORT:
     returnToPage();
     exit;
     break;
}

if (empty($label)) {
    $label = _("Describe the Problem");
}

$title = _("Problem Description");
$js_onLoad = null;
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_BASE . '/navbar.php';
require HORDE_TEMPLATES . '/problem/problem.inc';
if ($browser->hasFeature('javascript')) {
    include HORDE_TEMPLATES . '/problem/javascript.inc';
}
require HORDE_TEMPLATES . '/common-footer.inc';
