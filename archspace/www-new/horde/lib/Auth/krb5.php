<?php
/*
 * $Horde: horde/lib/Auth/krb5.php,v 1.1.2.6 2003/02/18 00:33:01 jan Exp $
 *
 * Copyright 2002-2003 Michael Slusarz <slusarz@bigworm.colorado.edu>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

Horde::functionCheck('krb5_login', true,
    'Auth_krb5: Required kerberos functions were not found.');

/**
 * The Auth_krb5 class provides an kerberos implementation of the Horde
 * authentication system.
 *
 * Kerberos must be correctly configured on your system for this class to
 * work correctly.
 * Additionally, this driver requires the 'krb5' PHP extension to be loaded.
 * The module can be downloaded here:
 *   http://bigworm.colorado.edu/phpkrb5/
 *
 * @author  Michael Slusarz <slusarz@bigworm.colorado.edu>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.auth
 */
class Auth_krb5 extends Auth {

    /**
     * Hash containing connection parameters.
     *
     * @var array $params
     */
    var $params = array();


    /**
     * Constructs a new Kerberos permissions object.
     *
     * @param optional array $params  A hash containing connection parameters.
     */
    function Auth_krb5($params = array())
    {
        $this->setParams($params);
    }

    /**
     * Set parameters.
     *
     * @param array $params  The parameter hash.
     */
    function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  An array of login credentials.
     *                            For kerberos, this must contain a password
     *                            entry.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    function _authenticate($userID, $credentials)
    {
        if (!array_key_exists('password', $credentials)) {
            return false;
        }
        $result = krb5_login($userID, $credentials['password']);

        /* Results: KRB5_OK, KRB5_NOTOK, KRB5_BAD_PASSWORD, KRB5_BAD_USER */
        if ($result === KRB5_OK) {
            return true;
        } else {
            if ($result === KRB5_BAD_PASSWORD) {
                $this->_setAuthError(_("Bad kerberos password."));
            } elseif ($result === KRB5_BAD_USER) {
                $this->_setAuthError(_("Bad kerberos username."));
            } else {
                $this->_setAuthError(_("Kerberos server rejected authentication."));
            }
            return false;
        }
    }

}
?>
