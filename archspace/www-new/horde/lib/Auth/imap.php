<?php
/*
 * $Horde: horde/lib/Auth/imap.php,v 1.4.2.5 2003/01/03 12:48:23 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Gaudenz Steinlin <gaudenz.steinlin@id.unibe.ch>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */


Horde::functionCheck('imap_open', true,
    'Auth_imap: Required IMAP functions were not found.');

/**
 * The Auth_imap class provides an IMAP implementation of the Horde
 * authentication system.
 *
 * Parameters:
 *  'hostspec'      The hostname or IP address of the server.
 *  'protocol'      The connection protocol (e.g. 'imap', 'pop3', 'nntp').
 *  'port'          The server port to which we will connect.
 *  'folder'        The initial folder / mailbox to open.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Gaudenz Steinlin <gaudenz.steinlin@id.unibe.ch>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.auth
 */
class Auth_imap extends Auth {

    /**
     * Hash containing connection parameters.
     * @var array $params
     */
    var $params = array();

    /**
     * Constructs a new IMAP authentication object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Auth_imap($params = array())
    {
        $this->setParams($params);
    }

    /**
     * Take a hash and build the connection string out of it.
     *
     * @param array $params   A hash specifying an IMAP mailbox.
     */
    function setParams($params)
    {
        if (empty($params['dsn'])) {
            if (isset($params['hostspec'])) {
                $params['dsn'] = '{' . $params['hostspec'];
            } else {
                $params['dsn'] = '{localhost';
            }

            if (isset($params['protocol'])) {
                $params['dsn'] .= '/' . $params['protocol'];
            } else {
                $params['dsn'] .= '/imap';
            }

            if (isset($params['port'])) {
                $params['dsn'] .= ':' . $params['port'] . '}';
            } else {
                $params['dsn'] .= ':143}';
            }

            if (isset($params['folder'])) {
                $params['dsn'] .= $params['folder'];
            }
        }
        $this->params = $params;
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID       The userID to check.
     * @param array  $credentials  An array of login credentials. For IMAP,
     *                             this must contain a password entry.
     *
     * @return boolean Whether or not the credentials are valid.
     */
    function authenticate($userID, $credentials)
    {
        if (Auth::checkAuth($userID)) {
            return true;
        }

        $imap = @imap_open($this->params['dsn'], $userID,
                           $credentials['password'], OP_HALFOPEN);

        if ($imap) {
            @imap_close($imap);
            Auth::setAuth($userID, $credentials);
            return true;
        }

        @imap_close($imap);

        return false;
    }

}
?>
