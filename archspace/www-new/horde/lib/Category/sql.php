<?php
/*
 * $Horde: horde/lib/Category/sql.php,v 1.8.2.16 2003/01/17 10:22:16 jan Exp $
 *
 * Copyright 1999-2003 Stephane Huther <shuther@bigfoot.com>
 * Copyright 2001-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Category_sql:: class provides an SQL implementation of the Horde
 * caqtegory system.
 *
 * Required values for $params:
 *      'phptype'        The database type (ie. 'pgsql', 'mysql, etc.).
 *      'hostspec'       The hostname of the database server.
 *      'protocol'       The communication protocol ('tcp', 'unix', etc.).
 *      'username'       The username with which to connect to the database.
 *      'password'       The password associated with 'username'.
 *      'database'       The name of the database.
 *      'table'          The name of the data table in 'database'.
 *
 * The table structure for the category system is as follows:
 *
 * Note: A group may be part of several groups.
 *
 * @todo Use executeMultiple when required, try to setup transactions,
 * undo/redo if possible
 *
 * @author  Stephane Huther <shuther@bigfoot.com>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.category
 *
 *
 */
class Category_sql extends Category {

    /**
     * Handle for the current database connection.
     * @var resource $_db
     */
    var $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     * @var boolean $connected
     */
    var $_connected = false;

    /**
     * Constructs a new SQL authentication object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Category_sql($params)
    {
        parent::Category($params);
        $this->_connect();
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean true.
     */
    function _connect()
    {
        if (!$this->_connected) {
            if (!is_array($this->_params)) {
                Horde::fatal(new PEAR_Error(_("No configuration information specified for SQL Categories.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['phptype'])) {
                Horde::fatal(new PEAR_Error(_("Required 'phptype' not specified in categories configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['hostspec'])) {
                Horde::fatal(new PEAR_Error(_("Required 'hostspec' not specified in categories configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['username'])) {
                Horde::fatal(new PEAR_Error(_("Required 'username' not specified in categories configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['password'])) {
                Horde::fatal(new PEAR_Error(_("Required 'password' not specified in categories configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['database'])) {
                Horde::fatal(new PEAR_Error(_("Required 'database' not specified in categories configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['table'])) {
                Horde::fatal(new PEAR_Error(_("Required 'table' not specified in categories configuration.")), __FILE__, __LINE__);
            }

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params, true);
            if (DB::isError($this->_db)) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            /* Enable the "portability" option. */
            $this->_db->setOption('optimize', 'portability');
            $this->_connected = true;
        }

        return true;
    }

    /**
     * Disconnect from the SQL server and clean up the connection.
     *
     * @return boolean true on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->_db->disconnect();
        }

        return true;
    }

    /**
     * Load (a subset of) the category tree into the $_categories
     * array.
     *
     * @param string $root  (optional) Which portion of the category
     *                      tree to load. Defaults to all of it.
     *
     * @return mixed  True on success or a PEAR_Error on failure.
     *
     * @note No check against circular references.
     * @access private
     */
    function _load($root = null)
    {
        // Do NOT use Category::exists() here; that would cause an
        // infinite loop.
        if (isset($this->_categories[$root]) ||
            (count($this->_categories) > 0 && $root == '-1')) {
            return true;
        }
        if (!empty($root) && $root != '-1') {
            $root = $this->_db->getAssoc(sprintf('SELECT 0, category_id, category_parents FROM %s' .
                                                 ' WHERE category_name = %s AND group_uid = %s',
                                                 $this->_params['table'],
                                                 $this->_db->quote($root),
                                                 $this->_db->quote($this->_params['group'])));
            if (count($root) == 0) {
                return false;
            }

            $pstring = $root[0][1] . ':' . $root[0][0] . '%';
            if (!empty($root[0][1])) {
                $ids = substr($root[0][1], 1);
                $pquery = ' OR category_id in (' . str_replace(':', ', ', $ids) . ')';
            } else {
                $pquery = '';
            }
            $data = $this->_db->getAll(sprintf('SELECT category_id, category_name, category_parents FROM %s' .
                                               ' WHERE (category_parents LIKE %s OR category_id = %s%s)'.
                                               ' AND group_uid = %s',
                                               $this->_params['table'],
                                               $this->_db->quote($pstring),
                                               $root[0][0],
                                               $pquery,
                                               $this->_db->quote($this->_params['group'])));
        } else {
            $data = $this->_db->getAll(sprintf('SELECT category_id, category_name, category_parents FROM %s' .
                                              ' WHERE group_uid = %s',
                                              $this->_params['table'],
                                              $this->_db->quote($this->_params['group'])));
        }

        return $this->import(CATEGORY_FORMAT_FETCH, $data);
    }

    /**
     * Add a category
     *
     * note: there is no check against circular reference!!!
     * @param string $name       The name of the category.
     * @param optional string $parent   the name of the parent category
     */
    function addCategory($category, $parent = '-1')
    {
        $this->_connect();

        $result = parent::addCategory($category, $parent);
        if (PEAR::isError($result)) {
            return $result;
        }

        if (is_subclass_of($category, 'CategoryObject')) {
            $name = $category->getName();
            $data = SerializeUnit::serializeUnit($category->getData(), CATEGORY_SERIALIZE_FORMAT);
            $ser = CATEGORY_SERIALIZE_FORMAT;
        } else {
            $name = $category;
            $data = '';
            $ser = SERIALIZEUNIT_NONE;
        }

        $id = $this->_db->nextId($this->_params['table']);
        if (DB::isError($id)) {
            return $id;
        }

        if ('-1' == $parent) {
            $parents = '';
        } else {
            $parents = $this->_db->getAssoc(sprintf('SELECT 0, category_id, category_parents FROM %s WHERE category_name = %s',
                                                    $this->_params['table'],
                                                    $this->_db->quote($parent)));
            if (DB::isError($parents)) {
                return $parents;
            }

            $parents = $parents[0][1] . ':' . $parents[0][0];
        }

        $query = sprintf('INSERT INTO %s (category_id, group_uid, category_name, category_data, user_uid, category_serialized, category_parents)' .
                         ' VALUES (%s, %s, %s, %s, %s, %s, %s)',
                         $this->_params['table'],
                         $id,
                         $this->_db->quote($this->_params['group']),
                         $this->_db->quote($name),
                         $this->_db->quote($data),
                         $this->_db->quote(Auth::getAuth()),
                         $ser,
                         $this->_db->quote($parents));

        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Remove a category from another one
     *
     * @param string $name       The name of the category.
     * @param optional string $parent       The name of the parent from where
     *                           we remove it. If default, we removed it from
     *                           every category.
     *                           0 means every branch
     *                           -1 mean the root
     *                           other means just one, not deeper
     */
    function removeCategory($category, $parent)
    {
        $this->_connect();

        $result = parent::removeCategory($category, $parent);
        if (PEAR::isError($result)) {
            return $result;
        }

        if (is_subclass_of($category, 'CategoryObject')) {
            $name = $category->getName();
        } else {
            $name = $category;
        }

        $query = sprintf('DELETE FROM %s WHERE group_uid = %s AND category_name = %s',
                         $this->_params['table'],
                         $this->_db->quote($this->_params['group']),
                         $this->_db->quote($name));

        /* Execute the query. */
        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            return $result;
        }

        if ($this->exists($name)) {
            return new PEAR_Error('Removal failed');
        }

        return true;
    }

    /**
     * Move a category from one parent to a new one.
     *
     * @param string $name       The name of the category.
     * @param string $old_parent The name of the old parent.
     * @param string $new_parent The name of the new parent.
     *
     * @note There is no check against circular references.
     */
    function moveCategory($name, $old_parent, $new_parent = '-1')
    {
        $this->_connect();

        $result = parent::moveCategory($name, $old_parent, $new_parent);
        if (PEAR::isError($result)) {
            return $result;
        }

        if (is_subclass_of($category, 'CategoryObject')) {
            $name = $category->getName();
        } else {
            $name = $category;
        }

        if ('-1' == $new_parent) {
            $new_parents = '';
        } else {
            $new_parents = $this->_db->getAssoc(sprintf('SELECT 0, category_id, category_parents FROM %s WHERE category_name = %s',
                                                        $this->_params['table'],
                                                        $this->_db->quote($new_parent)));
            if (DB::isError($new_parents)) {
                return $new_parents;
            }

            $new_parents = $new_parents[0][1] . ':' . $new_parents[0][0];
        }

        $query = sprintf('UPDATE %s SET category_parents = %s WHERE category_name = %s',
                         $this->_params['table'],
                         $this->_db->quote($new_parents),
                         $this->_db->quote($name));

        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Change a category's name.
     *
     * @param string $old_category  The old category.
     * @param string $new_category  The new category.
     */
    function renameCategory($old_category, $new_category)
    {
        $this->_connect();

        $result = parent::renameCategory($old_category, $new_category);
        if (PEAR::isError($result)) {
            return $result;
        }

        if (is_subclass_of($old_category, 'CategoryObject')) {
            $old_name = $old_category->getName();
        } else {
            $old_name = $old_category;
        }

        if (is_subclass_of($new_category, 'CategoryObject')) {
            $new_name = $new_category->getName();
        } else {
            $new_name = $new_category;
        }

        $query = sprintf('UPDATE %s SET category_name = %s' .
                         ' WHERE category_name = %s',
                         $this->_params['table'],
                         $this->_db->quote($new_name),
                         $this->_db->quote($old_name));

        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            return $result;
        }

        return true;
    }

    function getCategoryData($category)
    {
        $this->_connect();

        $query = sprintf('SELECT category_data FROM %s WHERE category_name = %s',
                         $this->_params['table'],
                         $this->_db->quote($category));

        return SerializeUnit::unSerializeUnit($this->_db->getOne($query), CATEGORY_SERIALIZE_FORMAT);
    }

    /**
     * Update the data in a category. Does not change the category's
     * parent or name, just serialized data.
     *
     * @param string $category  The category object.
     */
    function updateCategoryData($category)
    {
        $this->_connect();

        $result = parent::updateCategoryData($category);
        if (PEAR::isError($result)) {
            return $result;
        }

        if (!is_subclass_of($category, 'CategoryObject')) {
            // Nothing to do for non objects.
            return true;
        }

        $name = $category->getName();
        $data = SerializeUnit::serializeUnit($category->getData(), CATEGORY_SERIALIZE_FORMAT);
        $ser = CATEGORY_SERIALIZE_FORMAT;

        $query = sprintf('UPDATE %s SET category_data = %s, category_serialized = %s' .
                         ' WHERE category_name = %s',
                         $this->_params['table'],
                         $this->_db->quote($data),
                         $this->_db->quote($ser),
                         $this->_db->quote($name));

        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            return $result;
        }

        return true;
    }

}
?>
