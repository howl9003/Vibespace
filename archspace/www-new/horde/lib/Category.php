<?php
/*
 * $Horde: horde/lib/Category.php,v 1.8.2.15 2003/04/28 19:59:07 jan Exp $
 *
 * Copyright 1999-2003 Stephane Huther <shuther@bigfoot.com>
 * Copyright 2001-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once HORDE_BASE . '/lib/Serialize.php';

/**
 * Required values for $params:
 * groupid: define each group of categories we want to build
 */

// Available import/export formats.
/** @constant CATEGORY_FORMAT_TREE List every category in an array,
    similar to PEAR/html/menu.php */
define('CATEGORY_FORMAT_TREE', 1);

/** @constant CATEGORY_FORMAT_FETCH List every category in an array
    child-parent. Comes from driver pear/sql */
define('CATEGORY_FORMAT_FETCH', 2);

/** @constant CATEGORY_FORMAT_FLAT Get a full list - an array of keys */
define('CATEGORY_FORMAT_FLAT', 3);

/** @constant CATEGORY_FORMAT_3D Use a specific format, comes from the
    project olbookmarks - sourceforge / libdrawtree.php:

    $data[0][0]['name'] = 'Root';    $data[0][0]['p'] = '0.0';
    $data[1][1]['name'] = 'dir1';    $data[1][1]['p'] = '0.0';
    $data[2][2]['name'] = 'subdir1'; $data[2][2]['p'] = '1.1';
    $data[3][3]['name'] = 'data1';   $data[3][3]['p'] = '2.2';
    $data[3][4]['name'] = 'data2';   $data[3][4]['p'] = '2.2';
    $data[3][5]['name'] = 'data3';   $data[3][5]['p'] = '2.2';
    $data[2][6]['name'] = 'subdir2'; $data[2][6]['p'] = '1.1';
    $data[1][7]['name'] = 'dir2';    $data[1][7]['p'] = '0.0';
    $data[2][8]['name'] = 'subdir3'; $data[2][8]['p'] = '1.7';
    $data[2][9]['name'] = 'subdir4'; $data[2][9]['p'] = '1.7';
*/
define('CATEGORY_FORMAT_3D', 4);

// Format used to serialize
define('CATEGORY_SERIALIZE_FORMAT', SERIALIZEUNIT_BASIC);

/**
 * The Category:: class provides a common abstracted interface into
 * the various backends for the Horde Categories system.
 *
 * A category is just a title that is saved in the page for the null
 * driver or can be saved in a database to be accessed from
 * everywhere. Every category must have a different name (for a same
 * groupid). A category may have different parent categories.
 *
 * @note -1 is used as the root, but it is a STRING, it is important
 * because database methods in PHP work only with strings, so we avoid
 * confusion.
 *
 * @author  Stephane Huther <shuther@bigfoot.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.category
 */
class Category {

    /**
     * Array of all categories: indexed by name = parent right now,
     * the format is array(name => array(parents)), but it could
     * change to an XML/DOM organization.
     * @var array $categories
     */
    var $_categories = array();

    /**
     * Hash containing connection parameters.
     * @var array $_params
     */
    var $_params = array();

    /**
     * Constructor
     * @param array  $params A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *                       here, we need  'groupid' = a constant that defines
     *                       in each group we will work
     */
    function Category($params)
    {
        $this->_params = $params;
    }

    /**
     * Attempts to return a concrete Category instance based on
     * $driver.
     *
     * @param mixed $driver  The type of concrete Category subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Category/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array $params  A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *                       here, we need 'groupid' = a string that defines
     *                       top-level categories of categories.
     *
     * @return object Category  The newly created concrete Category instance,
     *                          or false on an error.
     */
    function &factory($driver, $params)
    {
        $driver = strtolower($driver);

        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Category($params);
        }

        if (!empty($app)) {
            include_once $GLOBALS['registry']->getParam('fileroot', $app) . '/lib/Category/' . $driver . '.php';
        } elseif (@file_exists(dirname(__FILE__) . '/Category/' . $driver . '.php')) {
            include_once dirname(__FILE__) . '/Category/' . $driver . '.php';
        } else {
            @include_once 'Horde/Category/' . $driver . '.php';
        }
        $class = 'Category_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }
    }

    /**
     * Attempts to return a reference to a concrete Category instance based on
     * $driver. It will only create a new instance if no Category instance
     * with the same parameters currently exists.
     *
     * This should be used if multiple category sources (and, thus,
     * multiple Category instances) are required.
     *
     * This method must be invoked as: $var = &Category::singleton()
     *
     * @param mixed $driver  The type of concrete Category subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Category/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array  $params (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Category  The concrete Category reference, or false on an
     *                        error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;
        if (!isset($instances)) $instances = array();

        if (is_array($driver)) {
            $drivertag = implode(':', $driver);
        } else {
            $drivertag = $driver;
        }
        $signature = md5(strtolower($drivertag) . '][' . implode('][', $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Category::factory($driver, $params);
        }

        return $instances[$signature];
    }

    /**
     * Add a category
     *
     * Note: there is no check against circular reference.
     *
     * @param mixed $category    The name of the category.
     *                       If it is a string, just the name, if it is a
     *                       sub-class of CategoryObject, we get the real
     *                       information from this object (getData)
     * @param optional string $parent   the name of the parent category
     *
     * @access protected
     */
    function addCategory($category, $parent = '-1', $extended = null)
    {
        if (is_subclass_of($category, 'CategoryObject')) {
            $data = $category->getData();
            $category = $category->getName();
        }

        if ($this->exists($category, $parent)) {
            return new PEAR_Error('Already exists');
        } elseif ($parent != '-1' && !isset($this->_categories[$parent])) {
            return new PEAR_Error('Add failed');
        } elseif (!isset($this->_categories[$category])) {
            $this->_categories[$category] = array();
        }

        $this->_categories[$category][$parent] = true;

        return true;
    }

    /**
     * Remove a category
     *
     * @param string $category          The category to remove.
     *
     * @param optional string $parent   The name of the parent category to remove $category from.
     *                                  If default, we removed it from every category.
     *                                    0 means every branch
     *                                    -1 means the root
     *                                    Other means just that sub-category
     * @param optional boolean force [default = false] Force to remove
     *                         every child NOT YET IMPLEMENTED
     *
     * @note, the extended status is not removed!
     */
    function removeCategory($category, $parent = '0', $force = false)
    {
        if ($force) {
            return PEAR::raiseError('Not supported');
        }

        if (is_subclass_of($category, 'CategoryObject')) {
            $category = $category->getName();
        }

        if ($this->exists($category, $parent) != true) {
            return PEAR::raiseError('Does not exist');
        }

        switch ($parent) {
        case '0':
            unset($this->_categories[$category]);
            break;

        case '-1':
            if (!isset($this->_categories[$category][$parent])) {
                return new PEAR_Error('Does not exist');
            }
            unset($this->_categories[$category][$parent]);
            break;

        default:
            if (!isset($this->_categories[$category][$parent])) {
                return new PEAR_Error('Does not exist');
            }
            unset($this->_categories[$category][$parent]);
        }

        if (isset($this->_categories[$category]) &&
            count($this->_categories[$category]) == 0) {
            unset($this->_categories[$category]);
        }

        return true;
    }

    /**
     * Move a category from one parent to a new one.
     *
     * @param string $category   The name of the category.
     * @param string $old_parent The name of the old parent.
     * @param string $new_parent The name of the new parent.
     *
     * @note There is no check against circular references.
     */
    function moveCategory($category, $old_parent, $new_parent)
    {
        if (is_subclass_of($category, 'CategoryObject')) {
            $category = $category->getName();
        }

        if ($this->exists($category, $old_parent) != true) {
            return new PEAR_Error('Does not exist');
        }
        if ($this->exists($new_parent) != true) {
            return new PEAR_Error('Does not exist');
        }

        unset($this->_categories[$category][$old_parent]);
        $this->_categories[$category][$new_parent] = true;

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
        if (is_subclass_of($old_category, 'CategoryObject')) {
            $old_name = $old_category->getName();
        } else {
            $old_name = $old_category;
        }

        if (is_subclass_of($new_category, 'CategoryObject')) {
            $new_name = $new_category->getName();
            $new_data = $new_category->getData();
        } else {
            $new_name = $new_category;
            $new_data = null;
        }

        if (!$this->exists($old_name)) {
            return new PEAR_Error('Does not exist');
        }
        if ($this->exists($new_name)) {
            return new PEAR_Error('Duplicate name');
        }

        $this->_categories[$new_name] = $this->_categories[$old_name];
        unset($this->_categories[$old_name]);

        return true;
    }

    /**
     * Return a CategoryObject (or subclass) object of the data in the category.
     *
     * @param          string $category The name of the category to fetch.
     * @param optional string $class    The subclass of CategoryObject to use. Defaults
     *                                  to CategoryObject.
     */
    function &getCategory($category, $class = 'CategoryObject')
    {
        $this->_load($category);
        if (!class_exists($class)) {
            return PEAR::raiseError($class . ' not found');
        }
        if (empty($this->_categories[$category])) {
            return PEAR::raiseError($category . ' not found');
        }

        $categoryOb = new $class($category);
        $categoryOb->data = $this->getCategoryData($category);
        return $categoryOb;
    }

    function getCategoryData($category)
    {
        return null;
    }

    /**
     * Update the data in a category. Does not change the category's
     * parent or name, just serialized data.
     *
     * @param string $category  The category object.
     */
    function updateCategoryData($category)
    {
        if (is_subclass_of($category, 'CategoryObject')) {
            $name = $category->getName();
        } else {
            // Nothing to do for non-objects.
            return true;
        }

        if (!$this->exists($name)) {
            return new PEAR_Error('Does not exist');
        }

        return true;
    }

    /**
     * Export a list of categories
     *
     * @param integer $format       Format of the export
     * @param string optional $parent The name of the parent from
     *                                where we export.
     *
     * @return mixed - usually an array
     *
     */
    function export($format, $parent = '-1')
    {
        $this->_load($parent);
        $out = array();

        switch ($format) {
        case CATEGORY_FORMAT_TREE:
            $this->extractAllLevelTree($out, $parent);
            break;

        case CATEGORY_FORMAT_FLAT:
            $this->extractAllLevelList($out2, $parent);
            if (empty($out2)) {
                $out[$parent] = true;
            } else {
                foreach ($out2 as $key => $val) {
                    $out[$key] = true;
                    foreach ($val as $kkey => $vval) {
                        $out[$kkey] = true;
                    }
                }
            }
            break;

        case CATEGORY_FORMAT_3D:
            $out2 = $this->export(CATEGORY_FORMAT_TREE, $parent);
            $id = 0;
            $this->map3d($out, $out2, 0, $id, 0);
            break;

        default:
            return PEAR::raiseError('Not supported');
        }

        return $out;
    }

    /**
     * Used by the export function to handle CATEGORY_FORMAT_3D.
     *
     * @param array   $out   Array that will contain the result
     * @param array   $arr   Array from export(CATEGORY_FORMAT_TREE)
     * @param integer $depth Depth of the child
     * @param integer $id    kind of auto increment value
     * @param integer $pId   $id of the parent, the depth will be $depth - 1
     *
     * @access private
     * @see export()
     */
    function map3d(&$out, $arr, $depth, &$id, $pId)
    {
        foreach ($arr as $key => $val) {
            if (0 == $depth) {
                $pDepth = 0;
            } else {
                $pDepth = $depth - 1;
            }

            if ('-1' == $key) {
                $key = 'root';
            }

            $out[$depth][$id]['p'] = $pDepth . '.' . $pId;
            $out[$depth][$id]['name'] = $key;

            if (!isset($out['x']) || $depth > $out['x']) {
                $out['x'] = $depth;
            }
            if (!isset($out['y']) || $id > $out['y']) {
                $out['y'] = $id;
            }

            $id = $id + 1;
            if (is_array($val)) {
                $this->map3d($out, $val, $depth + 1, $id, $id - 1);
            }
        }
    }

    /**
     * Import a list of categories. Used by drivers to populate the
     * internal $categories array.
     * @access private
     *
     * @param integer $format  Format of the import (CATEGORY_FORMAT_*).
     * @param array $data      The data to import.
     */
    function import($format, $data)
    {
        switch ($format) {
        case CATEGORY_FORMAT_FETCH:
            $cats = array();
            $cdata = array();
            $cids = array();
            foreach ($data as $cat) {
                $cids[$cat[0]] = $cat[1];
                $cats[$cat[1]] = $cat[2];
            }
            foreach ($cats as $cat => $parents) {
                if (!empty($parents)) {
                    $parents = explode(':', substr($parents, 1));
                    $par = $parents[count($parents) - 1];
                    $cdata[$cat] = array($cids[$par] => true);
                } else {
                    $cdata[$cat] = array('-1' => true);
                }
            }

            $this->_categories = array_merge_recursive($this->_categories, $cdata);
            break;

        default:
            return PEAR::raiseError('Not supported');
        }

        return true;
    }

    /**
     * Give the number of children a category has. We are talking
     * about immediate children, not grandchildren, etc.
     *
     * @param string optional $parent The name of the parent from
     *                                where we begin.
     *
     * @return integer
     * @todo could be easily optimized ;-)
     */
    function getNumberOfChildren($parent = '-1')
    {
        if (is_subclass_of($parent, 'CategoryObject')) {
            $parent = $parent->getName();
        }
        $out = $this->extractOneLevel($parent);
        return isset($out[$parent]) ? count($out[$parent]) : 0;
    }

    /**
     * Extract one level of categories, based on a parent, get the childs
     * format parent - name
     * We can see this function as a way to get a collection of node's children
     *
     * @param string optional $parent The name of the parent from
     *                                where we begin.
     *
     * @return array
     */
    function extractOneLevel($parent = '-1')
    {
        $out = array();
        foreach ($this->_categories as $category => $qparent) {
            foreach ($qparent as $vparent => $notuse) {
                if ($vparent == $parent) {
                    if (!isset($out[$parent])) $out[$parent] = array();
                    $out[$parent][$category] = true;
                }
            }
        }
        return $out;
    }


    /**
     * Extract all level of categories, based on a parent
     * Tree format
     *
     * @param array $out    Contain the result
     * @param string optional $parent The name of the parent from
     *                                where we begin.
     * @param integer optional $maxlevel The number of level of depth to check it
     *
     * Note, if nothing is returned that means there is no child, but
     * don't forget to add the parent if you make some operations!
     */
    function extractAllLevelTree(&$out, $parent='-1', $level=-1)
    {
        if ($level == 0) {
            return false;
        }

        $k = $this->extractOneLevel($parent);
        if (!isset($k[$parent])) {
            return false;
        }

        $k = $k[$parent];
        foreach ($k as $category => $v) {
            if (!isset($out[$parent]) || !is_array($out[$parent])) {
                $out[$parent] = array();
            }
            $out[$parent][$category] = true;
            $this->extractAllLevelTree($out[$parent], $category, $level - 1);
        }
    }

    /**
     * Extract all level of categories, based on a parent
     * List format: array(parent => array(child => true))
     *
     * @param string optional $parent The name of the parent from
     *                                where we begin.
     * @param integer optional $maxlevel The number of levels of depth to check it
     * @param array $out    Contain the result
     *
     * Note, if nothing is returned that means there is no child, but
     * don't forget to add the parent if you make some operations!
     */
    function extractAllLevelList(&$out, $parent = '-1', $level = -1)
    {
        if ($level == 0) {
            return false;
        }

        $k = $this->extractOneLevel($parent);
        if (!isset($k[$parent])) {
            return false;
        }

        $k = $k[$parent];

        foreach ($k as $category => $v) {
            if (!isset($out[$parent])) {
                $out[$parent] = array();
            }
            if (!isset($out[$parent][$category])) {
                $out[$parent][$category] = true;
                $this->extractAllLevelList($out, $category, $level - 1);
            }
        }
    }

    /**
     * Get a list of parents, based on a child - just one level
     *
     * @param string $child           The name of the child.
     * @param optional string $parent The name of the parent from where
     *                                we want to check.
     *
     * @return array
     */
    function getImmediateParents($child, $parentfrom = '0')
    {
        if (is_subclass_of($child, 'CategoryObject')) {
            $child = $child->getName();
        }
        if ($this->exists($child, $parentfrom) != true) {
            return new PEAR_Error('Does not exist');
        }

        return $this->_categories[$child];
    }

    /**
     * Get a list of parents, based on a child - every levels
     *
     * @param string $child The name of the child
     * @param optional string $parent The name of the parent from where
     *                           we want to check.
     * @return array [child] [parent] with a tree format
     */
    function getParents($child, $parentfrom = '0')
    {
        $ret = $this->getImmediateParents($child, $parentfrom);
        if (!is_array($ret)) {
            return new PEAR_Error('Parents not found');
        }

        foreach ($ret as $parent => $trueval) {
            if ($parent != '-1') {
                $ret[$parent]=$this->getParents($parent);
            }
        }

        return $ret;
    }

    /**
     * Check if a category exists or not. The category -1 always
     * exists.
     *
     * @param string $category The name of the category
     *
     * @return boolean  True if the category exists, false otherwise.
     */
    function exists($category)
    {
        $this->_load($category);
        if (is_subclass_of($category, 'CategoryObject')) {
            $category = $category->getName();
        }
        if ($category == '-1') {
            return true;
        }
        if (!array_key_exists($category, $this->_categories)) {
            return false;
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
    }

}

/**
 * Class that can be extended to save arbitrary information as part of
 * a category. The Groups system makes use of this; the
 * CategoryObject_Group class is an example of an extension of this
 * class with specialized methods.
 *
 * @author  Stephane Huther <shuther@bigfoot.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.category
 */
class CategoryObject {

    /**
     * Key-value hash that will be serialized.
     * @see getData()
     * @var array $data
     */
    var $data = array();

    /**
     * The unique name of this category. These names have the same
     * requirements as other category names - they must be unique,
     * etc.
     * @var string $name.
     */
    var $name;


    /**
     * CategoryObject constructor. Just sets the $name parameter.
     *
     * @param string $name The category name.
     */
    function CategoryObject($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this category.
     *
     * @return string The category name.
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Get a pointer/accessor to the array that we will save
     * needed because PHP is not an object language
     * @return array reference to the internal array to serialize
     */
    function &getData()
    {
        return $this->data;
    }

    /**
     * Merge the data of an array with the one already in the class
     * @param array $arr
     */
    function mergeData(&$arr)
    {
        $this->data = array_merge_recursive($this->getData(), $arr);
    }

}
?>
