<?php
/**
 * SessionHandler:: implementation for DBM files.
 *
 * $Horde: horde/lib/SessionHandler/dbm.php,v 1.3.2.3 2003/05/19 19:26:07 slusarz Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.session
 */
class SessionHandler_dbm extends SessionHandler {

    /**
     * Our pointer to the DBM file, if open.
     *
     * @var resource $_dbm
     */
    var $_dbm;

    /**
     * Constructs a new DBM SessionHandler object.
     *
     * @access public
     *
     * @param optional array $params  Unused.
     */
    function SessionHandler_dbm($params = array())
    {
    }

    function open($save_path, $session_name)
    {
        $this->_dbm = @dbmopen("$save_path/$session_name", 'c');
        return $this->_dbm;
    }

    function close()
    {
        return @dbmclose($this->_dbm);
    }

    function read($id)
    {
        $session_data = '';
        if ($data = dbmfetch($this->_dbm, $id)) {
            $session_data = base64_decode(substr($data, strpos($data, '|') + 1));
        }

        return $session_data;
    }

    function write($id, $session_data)
    {
        return @dbmreplace($this->_dbm, $id, time() . '|' . base64_encode($session_data));
    }

    function destroy($id)
    {
        $result = @dbmdelete($this->_dbm, $id);
        if (!$result) {
            Horde::logMessage('Failed to delete session (id = ' . $id . ')', __FILE__, __LINE__, LOG_ERR);
            return false;
        }

        return true;
    }

    function gc($maxlifetime = 300) 
    {
        $expired = time() - $maxlifetime;
        $id = dbmfirstkey($this->_dbm);
        while ($id) {
            if ($data = dbmfetch($this->_dbm, $id)) {
                $age = substr($tmp, 0, strpos($data, '|'));
                if ($expired > $age) {
                    $this->destroy($id);
                }
            }

            $id = dbmnextkey($this->_dbm, $id);
        }

        return true;
    }

}
