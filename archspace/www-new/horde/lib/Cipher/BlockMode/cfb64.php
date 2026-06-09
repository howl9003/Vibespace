<?php
/**
 * The Horde_Cipher_BlockMode_cfb64:: This class implements the
 * Horde_Cipher_BlockMode using a 64 bit cipher feedback. This can
 * used to encypt any length string and the encrypted version will be
 * the same length.
 *
 * $Horde: horde/lib/Cipher/BlockMode/cfb64.php,v 1.2.2.4 2003/04/12 20:50:41 slusarz Exp $
 *
 * Copyright 2002-2003 Mike Cochrane <mike@graftonhall.co.nz>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.cipher
 */
class Horde_Cipher_BlockMode_cfb64 extends Horde_Cipher_BlockMode {

    function encrypt(&$cipher, $plaintext)
    {
        $encrypted = '';

        $n = 0;
        $jMax = strlen($plaintext);
        for ($j = 0; $j < $jMax; $j++) {
            if ($n == 0) {
                $this->_iv = $cipher->encryptBlock($this->_iv);
            }

            $c = $plaintext[$j] ^ $this->_iv[$n];
            $this->_iv = substr($this->_iv, 0, $n) . $c . substr($this->_iv, $n + 1);
            $encrypted .= $c;

            $n = (++$n) & 0x07;
        }

        return $encrypted;
    }

    function decrypt(&$cipher, $ciphertext)
    {
        $decrypted = '';

        $n = 0;
        $jMax = strlen($ciphertext);
        for ($j = 0; $j < $jMax; $j++) {
            if ($n == 0) {
                $this->_iv = $cipher->encryptBlock($this->_iv);
            }

            $c = $ciphertext[$j] ^ $this->_iv[$n];
            $this->_iv = substr($this->_iv, 0, $n) . substr($ciphertext, $j, 1) . substr($this->_iv, $n + 1);
            $decrypted .= $c;

            $n = (++$n) & 0x07;
        }

        // Remove trailing \0's used to pad the last block
        while (substr($decrypted, -1, 1) == "\0") {
            $decrypted = substr($decrypted, 0, -1);
        }

        return $decrypted;
    }

}
