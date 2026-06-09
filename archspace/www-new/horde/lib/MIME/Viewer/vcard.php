<?php
/*
 * $Horde: horde/lib/MIME/Viewer/vcard.php,v 1.2.2.8 2003/01/03 13:23:20 jan Exp $
 *
 * Copyright 2002-2003 Jan Schneider <jan@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer_vcard class renders out vCards in HTML format.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.0
 * @package horde.mime.viewer
 */
class MIME_Viewer_vcard extends MIME_Viewer {

    /**
     * Render out the currently set contents in HTML format.  The
     * $mime_part class variable has the information to render out,
     * encapsulated in a MIME_Part object.
     */
    function render($params = null)
    {
        global $registry, $prefs;

        include_once HORDE_BASE . '/lib/Data.php';

        $vc = new Data_rfc2425();
        $vc->import($this->mime_part->getContents());

        ob_start();
        $title = _("vCard");
        include_once $registry->getParam('templates', 'horde') . '/common-header.inc';
        $html = ob_get_contents();
        ob_end_clean();

        for ($i = 0; $i < count($vc->cards); $i++) {
            $html .= '<table cellspacing="1"><tr><td colspan="2" class="header">';

            $fullname = $vc->getValues('FN', $i);
            $html .= $fullname[0]['value'];
            $html .= '</td></tr>';

            $name = $vc->getValues('N', $i);
            $name_parts = explode(';', $name[0]['value']);
            $name_arr = array();
            if (isset($name_parts[3])) $name_arr[] = $name_parts[3];
            if (isset($name_parts[1])) $name_arr[] = $name_parts[1];
            if (isset($name_parts[2])) $name_arr[] = $name_parts[2];
            if (isset($name_parts[0])) $name_arr[] = $name_parts[0];
            if (isset($name_parts[4])) $name_arr[] = $name_parts[4];
            $html .= $this->_row(_("Name"), implode(' ', $name_arr));

            $aliases = $vc->getValues('ALIAS', $i);
            if (count($aliases) > 0) {
                $alias_arr = array();
                foreach ($aliases as $alias) {
                    $alias_arr[] = $alias['value'];
                }
                $html .= $this->_row(_("Alias"), implode('<br />', $alias_arr));
            }

            $birthdays = $vc->getValues('BDAY', $i);
            if (count($birthdays) > 0) {
                include_once 'Date/Calc.php';
                $birthday = $vc->mapDate($birthdays[0]['value']);
                $html .= $this->_row(_("Birthday"), Date_Calc::dateFormat($birthday['mday'], $birthday['month'], $birthday['year'], '%Y-%m-%d'));
            }

            $labels = $vc->getValues('LABEL', $i);
            foreach($labels as $label) {
                if (isset($label['params']['TYPE'])) {
                    foreach($label['params']['TYPE'] as $type) {
                        $label['params'][strtoupper($type)] = true;
                    }
                }
                if (isset($label['params']['HOME'])) {
                    $html .= $this->_row(_("Home Address"), nl2br($label['value']));
                } elseif (isset($label['params']['WORK'])) {
                    $html .= $this->_row(_("Work Address"), nl2br($label['value']));
                } else {
                    $html .= $this->_row(_("Address"), nl2br($label['value']));
                }
            }

            $numbers = $vc->getValues('TEL', $i);
            foreach ($numbers as $number) {
                if (isset($number['params']['TYPE'])) {
                    foreach ($number['params']['TYPE'] as $type) {
                        $number['params'][strtoupper($type)] = true;
                    }
                }
                if (isset($number['params']['VOICE'])) {
                    if (isset($number['params']['HOME'])) {
                        $html .= $this->_row(_("Home Phone"), $number['value']);
                    } elseif (isset($number['params']['WORK'])) {
                        $html .= $this->_row(_("Work Phone"), $number['value']);
                    } elseif (isset($number['params']['CELL'])) {
                        $html .= $this->_row(_("Cell Phone"), $number['value']);
                    } else {
                        $html .= $this->_row(_("Phone"), $number['value']);
                    }
                } elseif (isset($number['params']['FAX'])) {
                    $html .= $this->_row(_("Fax"), $number['value']);
                }
            }

            $addresses = $vc->getValues('EMAIL', $i);
            $emails = array();
            foreach ($addresses as $address) {
                if (isset($address['params']['TYPE'])) {
                    foreach ($address['params']['TYPE'] as $type) {
                        $address['params'][strtoupper($type)] = true;
                    }
                }
                if (isset($address['params']['INTERNET'])) {
                    $email = '<a href="';
                    $app = $registry->getMethod('mail/compose');
                    if (isset($app)) {
                        $email .= $registry->linkByPackage($app, 'mail/compose', array('to' => $address['value']));
                    } else {
                        $email .= 'mailto:' . $address['value'];
                    }
                    $email .= '">' . $address['value'] . '</a>';
                    if (isset($address['params']['PREF'])) {
                        array_unshift($emails, $email);
                    } else {
                        array_push($emails, $email);
                    }
                }
            }
            if (count($emails) > 0) {
                $html .= $this->_row(_("Email"), implode('<br />', $emails));
            }

            $title = $vc->getValues('TITLE', $i);
            if (count($title) > 0) {
                $html .= $this->_row(_("Title"), $title[0]['value']);
            }

            $role = $vc->getValues('ROLE', $i);
            if (count($role) > 0) {
                $html .= $this->_row(_("Role"), $role[0]['value']);
            }

            $org = $vc->getValues('ORG', $i);
            if (count($org) > 0) {
                $html .= $this->_row(_("Company"), $org[0]['value']);
            }

            $notes = $vc->getValues('NOTE', $i);
            if (count($notes) > 0) {
                $html .= $this->_row(_("Notes"), nl2br($notes[0]['value']));
            }

            $url = $vc->getValues('URL', $i);
            if (count($url) > 0) {
                $html .= $this->_row(_("URL"), '<a href="' . $url[0]['value'] . '" target="_blank">' . $url[0]['value'] . '</a>');
            }

            $html .= "</table><br />\n";
        }

        ob_start();
        if (isset($app)) {
            $registry->includeFiles($app, 'mail/compose');
        }
        include_once $registry->getParam('templates', 'horde') . '/common-footer.inc';
        $html .= ob_get_contents();
        ob_end_clean();

        return $html;
    }

    function _row($label, $value)
    {
        return '<tr class="item"><td>' . $label . '</td><td>' . $value . "</td></tr>\n";
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
?>
