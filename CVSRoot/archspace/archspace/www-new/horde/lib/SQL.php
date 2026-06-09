<?php
/**
 * This is a utility class, every method is static.
 *
 * $Horde: horde/lib/SQL.php,v 1.10.2.3 2003/01/03 13:23:27 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde
 */
class Horde_SQL {

    function readBlob($dbh, $table, $field, $criteria)
    {
        if (!count($criteria)) {
            return PEAR::raiseError('You must specify the fetch criteria');
        }

        $where = '';

        switch ($dbh->dbsyntax) {
        case 'oci8':
            foreach ($criteria as $key => $value) {
                if (!empty($where)) {
                    $where .= ' AND ';
                }
                if (empty($value)) {
                    $where .= $key . ' IS NULL';
                } else {
                    $where .= $key . ' = ' . $dbh->quote($value);
                }
            }

            $statement = OCIParse($dbh->connection,
                                  sprintf('SELECT %s FROM %s WHERE %s',
                                          $field, $table, $where));
            OCIExecute($statement);
            if (OCIFetchInto($statement, $lob)) {
                $result = $lob[0]->load();
            } else {
                $result = PEAR::raiseError('Unable to load SQL Data');
            }
            OCIFreeStatement($statement);
            break;

        default:
            foreach ($criteria as $key => $value) {
                if (!empty($where)) {
                    $where .= ' AND ';
                }
                $where .= $key . ' = ' . $dbh->quote($value);
            }
            $result = $dbh->getOne(sprintf('SELECT %s FROM %s WHERE %s',
                                           $field, $table, $where));

            switch ($dbh->dbsyntax) {
            case 'pgsql':
                $data = substr($result, 2);
                $result = pack('H' . strlen($data), $data);
                break;
            }
        }

        return $result;
    }

    function insertBlob($dbh, $table, $field, $data, $attributes)
    {
        $fields = array();
        $values = array();

        switch ($dbh->dbsyntax) {
        case 'oci8':
            foreach ($attributes as $key => $value) {
                $fields[] = $key;
                $values[] = $dbh->quote($value);
            }

            $statement = OCIParse($dbh->connection,
                                  sprintf('INSERT INTO %s (%s, %s)' .
                                          ' VALUES (%s, EMPTY_BLOB()) RETURNING %s INTO :blob',
                                          $table,
                                          implode(', ', $fields),
                                          $field,
                                          implode(', ', $values),
                                          $field));

            $lob = OCINewDescriptor($dbh->connection);
            OCIBindByName($statement, ':blob', $lob, -1, SQLT_BLOB);
            OCIExecute($statement, OCI_DEFAULT);
            $lob->save($data);
            $result = OCICommit($dbh->connection);
            $lob->free();
            OCIFreeStatement($statement);
            return $result ? true : PEAR::raiseError('Unknown Error');

        default:
            foreach ($attributes as $key => $value) {
                $fields[] = $key;
                $values[] = $value;
            }

            $query = sprintf('INSERT INTO %s (%s, %s) VALUES (%s)',
                             $table,
                             implode(', ', $fields),
                             $field,
                             '?' . str_repeat(', ?', count($values)));
            break;
        }

        switch ($dbh->dbsyntax) {
        case 'mssql':
        case 'pgsql':
            $values[] = bin2hex($data);
            break;

        default:
            $values[] = $data;
        }

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('SQL Query by Horde_SQL::insertBlob(): query = "%s"', $query),
                          __FILE__, __LINE__, LOG_DEBUG);

        /* Execute the query. */
        $stmt = $this->_db->prepare($query);
        return $this->_db->execute($stmt, $values);
    }

    function updateBlob($dbh, $table, $field, $data, $where, $alsoupdate)
    {
        $fields = array();
        $values = array();

        switch ($dbh->dbsyntax) {
        case 'oci8':
            $wherestring = '';
            foreach ($where as $key => $value) {
                if (!empty($wherestring)) {
                    $wherestring .= ' AND ';
                }
                $wherestring .= $key . ' = ' . $dbh->quote($value);
            }

            $statement = OCIParse($dbh->connection,
                                  sprintf('SELECT %s FROM %s FOR UPDATE WHERE %s',
                                          $field,
                                          $table,
                                          $wherestring));

            OCIExecute($statement, OCI_DEFAULT);
            OCIFetchInto($statement, $lob);
            $lob[0]->save($data);
            $result = OCICommit($dbh->connection);
            $lob[0]->free();
            OCIFreeStatement($statement);
            return $result ? true : PEAR::raiseError('Unknown Error');

        default:
            $updatestring = '';
            $values = array();
            foreach ($alsoupdate as $key => $value) {
                $updatestring .= $key . ' = ?, ';
                $values[] = $value;
            }
            $updatestring .= $field . ' = ?';
            switch ($dbh->dbsyntax) {
            case 'mssql':
            case 'pgsql':
                $values[] = bin2hex($data);
                break;

            default:
                $values[] = $data;
            }

            $wherestring = '';
            foreach ($where as $key => $value) {
                if (!empty($wherestring)) {
                    $wherestring .= ' AND ';
                }
                $wherestring .= $key . ' = ?';
                $values[] = $value;
            }

            $query = sprintf('UPDATE %s SET %s WHERE %s',
                             $table,
                             $updatestring,
                             $wherestring);
            break;
        }

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('SQL Query by Horde_SQL::updateBlob(): query = "%s"', $query),
                          __FILE__, __LINE__, LOG_DEBUG);

        /* Execute the query. */
        $stmt = $dbh->prepare($query);
        return $dbh->execute($stmt, $values);
    }

}
