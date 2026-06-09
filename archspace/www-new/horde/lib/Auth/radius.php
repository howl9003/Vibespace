<?php
/*
 * $Horde: horde/lib/Auth/radius.php,v 1.12.2.1 2003/02/18 00:33:01 jan Exp $
 *
 * Copyright 2000, 2001, 2002 by Edwin Groothuis. All rights reserved.
 * Copyright 2002 Michael Slusarz <slusarz@bigworm.colorado.edu>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Auth_radius class provides a Radius implementation of the Horde
 * authentication system.
 *
 * Required parameters:
 * ====================
 * 'host'    --  The RADIUS host.
 * 'secret'  --  The RADIUS secret string for the host.
 *
 * Optional parameters:
 * ====================
 * 'port'    --  The port to use on the RADIUS server.
 * 'suffix'  --  The domain name to add to unqualified user names.
 *
 *
 * This code requires that raw sockets were enabled in PHP at compile-time:
 *   e.g. "./configure --enable-sockets".
 *
 * -----
 *
 * This code is adapted from radius_authenticaion.inc v1.2 by Edwin
 * Groothuis (edwin@mavetju.org) - http://www.mavetju.org/
 * Portions of the following source code derived from Edwin's original code
 * is subject to the following license:
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. All advertising materials mentioning features or use of this software
 *    must display the following acknowledgement:
 *         This product includes software developed by Edwin Groothuis.
 * 4. Neither the name of Edwin Groothuis may be used to endorse or
 *    promote products derived from this software without specific
 *    prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT
 * OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * -----
 *
 * @author  Michael Slusarz <slusarz@bigworm.colorado.edu>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 3.0
 * @package horde.auth
 */
class Auth_radius extends Auth {

    /**
     * Hash containing connection parameters.
     *
     * @var array $params
     */
    var $params = array();


    /**
     * Constructs a new Kerberos permissions object.
     *
     * @access public
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Auth_radius($params = array())
    {
        $this->setParams($params);
    }

    /**
     * Set parameters.
     *
     * @access public
     *
     * @param array $params  The parameter hash.
     */
    function setParams($params)
    {
        $this->params = $params;

        /* A RADIUS host is required. */
        if (!array_key_exists('host', $this->params)) {
            Horde::fatal(new PEAR_Error('Auth_radius requires a RADIUS host to connect to.'), __FILE__, __LINE__, false);
        }

        /* A RADIUS secret string is required. */
        if (!array_key_exists('secret', $this->params)) {
            Horde::fatal(new PEAR_Error('Auth_radius requires a RADIUS secret string.'), __FILE__, __LINE__, false);
        }

        /* Suffix to add to unqualified user names. */
        if (!array_key_exists('suffix', $this->params)) {
            $this->params['suffix'] = '';
        }

        /* Some RADIUS servers listen on port 1812, some on 1645. */
        if (!array_key_exists('port', $this->params)) {
            $this->params['port'] = getservbyname('radius', 'udp');
        }
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @access private
     *
     * @param string $username    The userID to check.
     * @param array $credentials  An array of login credentials.
     *                            For radius, this must contain a password
     *                            entry.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    function _authenticate($username, $credentials)
    {
        /* Password is required. */
        if (!array_key_exists('password', $credentials)) {
            return false;
        }

        $nasIP = explode('.', $_SERVER['SERVER_ADDR']);

        /* 17 is UDP, formerly known as PROTO_UDP. */
        $sock = socket_create(AF_INET, SOCK_DGRAM, 17);
        if ((socket_connect($sock, gethostbyname($this->params['host']), $this->params['port'])) === false) {
            $this->_setAuthError(socket_strerror(socket_last_error()));
            return false;
        }

        /* Add suffix, if necessary. */
        if (!strstr($username, '@')) {
            $username .= $this->params['suffix'];
        }

        /* Auth code. */
        $randomList = array();
        for ($i = 1; $i <= 16; $i++) {
            $randomList[] = 1 + rand() % 255;
        }
        $RA = call_user_func_array('pack', array_merge("C16", $randomList));

        $encryptedpass = $this->_radiusEncrypt($credentials['password'], $this->params['secret'], $RA);
        $thisidentifier = rand() % 256;

        $length = 4 +                           // Header
                  16 +                          // Auth code
                  6 +                           // Service type
                  2 + strlen($username) +       // User name
                  2 + strlen($encryptedpass) +  // User password
                  6;                            // Nas IP

        $data = pack("C4a*C6C2a*C2a*C6",
            // Header
            1, $thisidentifier, ($length / 256), ($length % 256),
            // Auth code
            $RA,
            // Service type
            6, 6, 0, 0, 0, 1,
            // User name
            1, 2 + strlen($username), $username,
            // User password
            2, 2 + strlen($encryptedpass), $encryptedpass,
            // Nas IP
            4, 6, $nasIP[0], $nasIP[1], $nasIP[2], $nasIP[3]
        );

        if (($a = socket_write($sock, $data, $length)) === false) {
            $this->_setAuthError(socket_strerror(socket_last_error($sock)));
            socket_close($sock);
            return false;
        }

        /* Get the answer from the RADIUS server. */
        $readdata = socket_read($sock, 1);
        socket_close($sock);

        /* RFC 2138: 
             2 -> Access-Accept
             3 -> Access-Reject */
        if (ord($readdata) == 2) {
            return true;
        } else {
            $this->_setAuthError(_("Authentication rejected by RADIUS server."));
            return false;
        }
    }

    /**
     * Encrypt the password for transport to the RADIUS server.
     * See RFC 2138 [3].
     *
     * @access private
     *
     * @param string $password  The user's password.
     * @param string $key       The RADIUS secret key.
     * @param string $RA        The RADIUS request authenticator.
     *
     * @return string  The encrypted password.
     */
    function _radiusEncrypt($password, $key, $RA)
    {
        $keyRA = $key . $RA;
        $md5checksum = md5($keyRA);
        $output = '';

        for ($i = 0; $i <= 15; $i++) {
            if ((2 * $i) > strlen($md5checksum)) {
                $m = 0;
            } else {
                $m = hexdec(substr($md5checksum, 2 * $i,2));
            }

            if ($i > strlen($password)) {
                $p = 0;
            } else {
                $p = ord(substr($password, $i, 1));
            }

            $output .= chr($m ^ $p);
        }

        return $output;
    }

}
?>
