<?php
/*
 * $Horde: horde/lib/Auth/smb.php,v 1.9.2.1 2003/02/18 00:33:02 jan Exp $
 *
 * Copyright 1999-2002 Jon Parise <jon@horde.org>
 * Copyright 2002 Marcus I. Ryan <marcus@riboflavin.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

Horde::functionCheck('smbauth_validate', true,
    'Auth_smb: Required extension ' .
    '<a href="http://www.tekrat.com/projects/smbauth/index.php">smbauth</a>' .
    ' does not appear to be loaded.  It must be loaded via php.ini extensions.');

/**
 * The Auth_smb class provides an SMB implementation of the Horde
 * authentication system.
 *
 * Required values for $params:
 *      'pdc_hostspec'      The hostname of the PDC SMB server.
 *      'bdc_hostspec'      The hostname of the BDC SMB server.
 *      'domain'            The domain to authenticate against.
 *
 * This module requires the smbauth extension for PHP (see
 * http://www.tekrat.com/projects/smbauth).  At the time of
 * this writing, the extension, and thus this module, only
 * supported authentication against a domain, and pdc and bdc
 * must be non-null and not equal to each other. In other words,
 * to use this module you must have a domain with at least one
 * PDC and one BDC.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Marcus I. Ryan <marcus@riboflavin.net>
 * @version $Revision: 1.1.1.1 $
 * @package horde.auth
 */
class Auth_smb extends Auth {

    /** An array of capabilities, so that the driver can report which
        operations it supports and which it doesn't.
        @var array $capabilities */
    var $capabilities = array('add'         => false,
                              'update'      => false,
                              'remove'      => false,
                              'list'        => false,
                              'transparent' => false,
                              'loginscreen' => false);

    /**
     * Hash containing connection parameters.
     * @var array $params
     */
    var $params = array();


    /**
     * Constructs a new SMB permissions object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Auth_smb($params = array())
    {
        if (isset($params['pdc_hostspec'])) {
            $this->params['pdc_hostspec'] = $params['pdc_hostspec'];
        }
        if (isset($params['bdc_hostspec'])) {
            $this->params['bdc_hostspec'] = $params['bdc_hostspec'];
        }
        if (isset($params['domain'])) {
            $this->params['domain'] = $params['domain'];
        }
    }


    /**
     * Find out if the given set of login credentials are valid.
     *
     * @param string $userID       The userID to check.
     * @param array  $credentials  An array of login credentials.
     *
     * @return boolean  True on success or a PEAR_Error object on failure.
     */
    function _authenticate($userID, $credentials)
    {
        /* Ensure we've been provided with all of the necessary parameters. */
        if (!isset($this->params['pdc_hostspec'])) {
            Horde::fatal(new PEAR_Error(_("Required 'pdc_hostspec' not specified in authentication configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['bdc_hostspec'])) {
            Horde::fatal(new PEAR_Error(_("Required 'bdc_hostspec' not specified in authentication configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['domain'])) {
            Horde::fatal(new PEAR_Error(_("Required 'domain' not specified in authentication configuration.")), __FILE__, __LINE__);
        }

        /* Authenticate */
        $rval = smbauth_validate($userID,
                                 $credentials['password'],
                                 $this->params['pdc_hostspec'],
                                 $this->params['bdc_hostspec'],
                                 $this->params['domain']);

        if ($rval === 0) {
            return true;
	} elseif ($rval === 1) {
	    Horde::fatal(new PEAR_Error(_("Failed to connect to SMB server.")), __FILE__, __LINE__);
	} elseif ($rval === 2) {
	    Horde::fatal(new PEAR_Error(_(smbauth_err2str($rval))), __FILE__, __LINE__);
	}
        return false;
    }
}
?>
