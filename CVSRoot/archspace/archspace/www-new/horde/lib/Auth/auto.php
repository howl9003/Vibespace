<?php
/**
 * The Auth_auto class transparently logs users in to Horde using ONE
 * username, either defined in the config or defaulting to
 * 'horde_user'. This is only for use in testing or behind a firewall;
 * it should NOT be used on a public, production machine.
 *
 * $Horde: horde/lib/Auth/auto.php,v 1.1.2.2 2003/01/03 12:48:23 jan Exp $
 *
 * Optional values for params:
 *   'username'      The username to authenticate everyone as.
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.auth
 */
class Auth_auto extends Auth {

    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array $capabilities
     */
    var $capabilities = array('add'         => false,
                              'update'      => false,
                              'remove'      => false,
                              'list'        => false,
                              'transparent' => true,
                              'loginscreen' => false);

    /**
     * Constructs a new Automatic authentication object.
     *
     * @access public
     *
     * @param optional array $params  A hash containing parameters.
     */
    function Auth_auto($params = array())
    {
        $this->_setParams($params);
    }

    /**
     * Set parameters for the Auth_auto object.
     *
     * @access private
     *
     * @param array $params  Parameters. None currently required,
     *                       'username' is optional.
     */
    function _setParams($params)
    {
        if (!isset($params['username'])) {
            $params['username'] = 'horde_user';
        }
        $this->_params = $params;
    }

    /**
     * Automatic authentication: Set the user
     * allowed IP block.
     *
     * @access public
     *
     * @return boolean  Whether or not the client is allowed.
     */
    function transparent()
    {
        Auth::setAuth($this->_params['username'],
                      array('transparent' => 1));
        return true;
    }

}
