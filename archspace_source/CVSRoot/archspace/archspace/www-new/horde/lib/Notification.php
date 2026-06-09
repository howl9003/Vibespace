<?php
/*
 * $Horde: horde/lib/Notification.php,v 1.8.2.8 2003/01/17 10:22:14 jan Exp $
 *
 * Copyright 2001-2003 Jan Schneider <jan@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Notification:: class provides a subject-observer pattern for
 * raising and showing messages of different types and to different
 * listeners.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.notification
 */
class Notification {

    /** Hash containing all attached listener objects.
        @var array $listeners */
    var $listeners = array();

     /**
     * Returns a reference to the global Notification object, only
     * creating it if it doesn't already exist.
     *
     * This method must be invoked as: $notification = &Notification::singleton()
     *
     * @return object The Horde Notification instance.
     */
    function &singleton()
    {
        static $notification;

        if (!isset($notification)) {
            $notification = new Notification();
        }

        return $notification;
    }

   /**
     * Initialize the notification system, set up any needed session
     * variables, etc. Should never be called except by
     * &Notification::singleton();
     *
     * @access private
     */
    function Notification()
    {
        // Make sure the message stack is registered in the session,
        // and obtain a global-scope reference to it.
        if (!session_is_registered('hordeMessageStacks')) {
            global $hordeMessageStacks;

            $hordeMessageStacks = array();
            $_SESSION['hordeMessageStacks'] = &$hordeMessageStacks;

            session_register('hordeMessageStacks');
        } elseif (!isset($GLOBALS['hordeMessageStacks'])) {
            $GLOBALS['hordeMessageStacks'] = &$_SESSION['hordeMessageStacks'];
        }
    }

    /**
     * Registers a listener with the notification object and includes
     * the necessary library file dynamically.
     *
     * @param string  $driver       The name of the listener to attach. These names must
     *                              be unique; further listeners with the same name will
     *                              be ignored.
     * @param array   $params       (optional) A hash containing any additional
     *                              configuration or connection parameters a listener
     *                              driver might need.
     * @param string  $class        (optional) The class name from which the driver
     *                              get instantiated if not the default one. If given
     *                              you have to include the library file containing
     *                              this class yourself.
     *                              This is useful if you want the listener driver to
     *                              be overriden by an application's implementation.
     */
    function attach($listener, $params = array(), $class = '')
    {
        global $hordeMessageStacks;

        $listener = strtolower(basename($listener));
        if (isset($this->listeners[$listener])) {
            return false;
        }

        if (empty($class)) {
            @include_once dirname(__FILE__) . '/Notification/' . $listener . '.php';
            $class = 'Notification_' . $listener;
        }
        if (class_exists($class)) {
            $this->listeners[$listener] = new $class($params);
            if (!isset($hordeMessageStacks[$listener])) {
                $hordeMessageStacks[$listener] = array();
            }
        } else {
            Horde::fatal(new PEAR_Error(sprintf(_("Notification listener %s not found."), $listener)), __FILE__, __LINE__);
            return false;
        }
    }

    /**
     * Add a message to the Horde message stack.
     *
     * @access public
     *
     * @param string $message   The text description of the message.
     * @param int    $type      (optional) The type of message: 'horde.error',
     *                          'horde.warning', 'horde.success', or 'horde.message'.
     * @param mixed  $listener  (optional) The listener where the message should be displayed.
     *                          May be a string or an array of strings with the listener names.
     *                          Defaults to 'status'.
     */
    function push($message, $type = 'horde.message', $listener = 'status')
    {
        if (!is_array($listener)) {
            $listener = array($listener);
        }
        foreach ($listener as $t) {
            $GLOBALS['hordeMessageStacks'][$t][] = array('type' => $type, 'message' => $message);
        }
    }

    /**
     * Passes the message stack to all listeners.
     */
    function notify()
    {
        foreach ($this->listeners as $listener) {
            $listener->notify($GLOBALS['hordeMessageStacks']);
        }
    }

    /**
     * Return the number of notification messages in the stack.
     *
     * @author David Ulevitch <davidu@everydns.net>
     *
     * @access public
     *
     * @param optional string $my_listener  The name of the listener.
     *
     * @return integer  The number of messages in the stack.
     *
     * @since Horde 2.2
     */
    function count($my_listener = '')
    {
        if ($my_listener == '') {
            $count = 0;
            foreach ($this->listeners as $listener) {
                $count += count($_SESSION['hordeMessageStacks'][$listener->getName()]);
            }
            return $count;
        } else {
            return @count($_SESSION['hordeMessageStacks'][$this->listeners[$my_listener]->getName()]);
        }
    }

}
