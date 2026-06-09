<?php
/*
 * $Horde: horde/lib/MIME/Viewer.php,v 1.11.2.8 2003/01/03 12:48:25 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The MIME_Viewer:: class provides an abstracted interface to
 * render out MIME types into HTML format.  It depends on a
 * set of MIME_Viewer_* drivers which handle the actual rendering,
 * and also a configuration file to map MIME types to drivers.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.mime.viewer
 */

class MIME_Viewer {

    var $mime_part;

    var $conf;

    /**
     * Attempts to return a concert MIME_Viewer_* object based on the
     * type of MIME_Part passed onto it.
     *
     * @param &MIME_Part Reference to a MIME_Part object with the information to be rendered
     */
    function &factory(&$mime_part)
    {
        global $mime_drivers_map, $mime_drivers, $registry;

        /* Check that we have a valid MIME_Part object */
        if (!is_object($mime_part) || get_class($mime_part) != 'mime_part') {
            return false;
        }

        /* Determine driver type from the MIME type */
        $mime_type = $mime_part->getType();
        if (!$mime_type) {
            return false;
        }

        /* Figure the correct driver for this MIME type */
        $app = $registry->getApp();
        $driver = MIME_Viewer::_getDriver($mime_type, $app);

        /* No application-specific module, so look for a general Horde one */
        if (!$driver && $app != 'horde') {
            $app = 'horde';
            $driver = MIME_Viewer::_getDriver($mime_type, $app);
        }

        /* Spawn the relevant driver, and return it (or false on failure) */
        @include_once MIME_Viewer::resolveDriver($driver, $app);
        $class = (($app == 'horde') ? '' : $app . '_') . 'MIME_Viewer_' . $driver;
        if (class_exists($class)) {
            return new $class($mime_part, $mime_drivers[$app][$driver]);
        } else {
            return false;
        }
    }

    /**
     * Constructor for MIME_Viewer
     *
     * @param &MIME_Part    Reference to a MIME_Part object with the information to be rendered
     */
    function MIME_Viewer(&$mime_part, $conf = array())
    {
        $this->mime_part = &$mime_part;
        $this->conf = $conf;
    }

    /**
     * Return the MIME type of the rendered content.  This can be overridden
     * by the individual drivers, depending on what format they output in.
     * By default, it passes through the MIME type of the object, or replaces
     * custom extension types with 'text/plain' to let the browser do a best-guess
     * render.
     *
     * @return string MIME-type of the output content
     */
    function getType()
    {
        if ($this->mime_part->type == 'x-extension') {
            return 'text/plain';
        } else {
            return $this->mime_part->getType();
        }
    }

    /**
     * Return the rendered version of the object.  Should be overridden
     * by individual drivers to perform custom tasks.
     *
     * The $mime_part class variable has the information to render,
     * encapsulated in a MIME_Part object.
     * @param mixed params Any optional parameters this driver needs at runtime
     * @return string Rendered version of the object
     */
    function render($params = null)
    {
        return $this->mime_part->getContents();
    }

    /**
     * Given a driver and an application, this returns the fully
     * qualified filesystem path to the driver source file.
     * @param $driver string Driver name
     * @param $app string Application name
     * @return string Filesystem path of the driver/application queried
     */
    function resolveDriver($driver = 'default', $app = 'horde')
    {
        global $registry;
        return $registry->applications[$app]['fileroot'] . "/lib/MIME/Viewer/$driver.php";
    }

    /**
     * Given an input MIME type and a module name, this function
     * resolves it into a specific output driver which can handle it.
     * @param $mimeType string MIME type to resolve
     * @param $module string Module in which to search for the driver (e.g. chora)
     * @return string Name of driver (e.g. 'enscript') or false if one could not be found
     */
    function _getDriver($mimeType, $module = 'horde')
    {
        global $mime_drivers, $mime_drivers_map, $registry;

        $driver = '';

        /* If an override exists for this MIME type, then use that */
        if (isset($mime_drivers_map[$module]['overrides'][$mimeType])) {
            $driver = $mime_drivers_map[$module]['overrides'][$mimeType];

        } else if (isset($mime_drivers_map[$module]['registered'])) {
            /* Iterate through the list of registered drivers, and see if
             * this MIME type exists in the MIME types that they claim to handle.
             * If the driver handles it, then assign it as the rendering driver */
            foreach ($mime_drivers_map[$module]['registered'] as $dr) {
                if (in_array($mimeType, $mime_drivers[$module][$dr]['handles'])) {
                    $driver = $dr;
                }
            }
        }

        /* If the 'default' driver exists in this module, fall back to that */
        if (empty($driver) && @is_file(MIME_Viewer::resolveDriver('default', $module))) {
            $driver = 'default';
        }

        return empty($driver) ? false : $driver;
    }

    /**
     * Given a MIME type, this function will return an appropriate icon
     * @param  string $mimeType MIME type that we need an icon for
     * @return string URL to an icon
     */
    function getIcon($mimeType)
    {
        global $registry;

        $icon = MIME_Viewer::_getIcon($mimeType, $registry->getApp());

        if (empty($icon) && $registry->getApp() != 'horde') {
            $icon = MIME_Viewer::_getIcon($mimeType, 'horde');
        }

        if (empty($icon)) {
            $icon = MIME_Viewer::_getIcon('text/plain', 'horde');
        }
        return $icon;
    }

    /**
     * Given an input MIME type and module, this function returns
     * the URL of an icon that can be associated with it
     * @param $mimeType string MIME type to get the icon for
     * @return string URL to an icon, or false if none could be found
     */
    function _getIcon($mimeType, $module = 'horde')
    {
        global $mime_drivers, $registry;

        $driver = MIME_Viewer::_getDriver($mimeType, $module);
        if (!$driver) return false;

        /* If a specific icon for this driver and mimetype is defined,
         * then use that */
        if (!isset($mime_drivers[$module][$driver]['icons'][$mimeType])) {
            /* If a default icon for this driver exists, use that */
            if (!isset($mime_drivers[$module][$driver]['icons']['default'])) {
                $icon = @$mime_drivers[$module]['default']['icons']['default'];
            } else {
                $icon = @$mime_drivers[$module][$driver]['icons']['default'];
            }
        } else {
            /* An exact match for this icon is present in the driver */
            $icon = $mime_drivers[$module][$driver]['icons'][$mimeType];
        }

        return $icon ? $registry->applicationWebPath("%application%/graphics/mime/$icon", $module) : '';
    }

    function getCharset()
    {
        return Lang::getCharset();
    }

}
?>
