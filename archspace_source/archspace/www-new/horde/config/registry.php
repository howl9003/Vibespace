<?php
/**
 *            registry.php -- Horde application registry
 *
 * $Horde: horde/config/registry.php.dist,v 1.52.2.32 2003/10/08 10:31:51 jan Exp $
 *
 * This configuration file is used by Horde to determine which Horde
 * applications are installed and where, as well as how they interact.
 */

/**
 * Handlers
 * --------
 * The following settings register particular Horde applications as handlers
 * for functionality intended to be common to multiple Horde applications.
 */

/* auth: Handler for user authentication.
 *   Uncomment the auth/login/logout lines if you want to let
 *   IMP handle the authentication for Horde. This avoids the
 *   "double login" while accessing IMP.
 */
// $this->registry['auth']['login'] = 'imp';
// $this->registry['auth']['logout'] = 'imp';

/* mail: Handler for sending mail. */
$this->registry['mail']['compose'] = 'imp';
$this->registry['mail']['composePopup'] = 'imp';

/* contacts: Handler for contacts management. */
$this->registry['contacts']['search'] = 'turba';
$this->registry['contacts']['add'] = 'turba';
$this->registry['contacts']['sources'] = 'turba';
$this->registry['contacts']['fields'] = 'turba';
/* These handlers are available with Turba 1.2 */
// $this->registry['contacts']['add_field'] = 'turba';
// $this->registry['contacts']['delete_field'] = 'turba';
// $this->registry['contacts']['get_field'] = 'turba';
// $this->registry['contacts']['list_field'] = 'turba';
// $this->registry['contacts']['import_vcard'] = 'turba';

/* events: Handler for events. */
$this->registry['events']['show'] = 'kronolith';

/* memos: Handler for memos/notepad. */
$this->registry['memos']['search'] = 'mnemo';
$this->registry['memos']['list'] = 'mnemo';
$this->registry['memos']['show'] = 'mnemo';
$this->registry['memos']['add'] = 'mnemo';

/* tasks: Handler for todo lists. */
$this->registry['tasks']['search'] = 'nag';
$this->registry['tasks']['list'] = 'nag';
$this->registry['tasks']['show'] = 'nag';
$this->registry['tasks']['add'] = 'nag';

/* events: Handler for filters.
 * These should only be activated if Ingo is actually installed. */
// $this->registry['filter']['blacklistFrom'] = 'ingo';
// $this->registry['filter']['whitelistFrom'] = 'ingo';


/**
 * Application registry
 * --------------------
 * The following settings register installed Horde applications.
 * By default, Horde assumes that the application directories live
 * inside the horde directory.
 *
 * Attribute     Type     Description
 * ---------     ----     -----------
 * fileroot      string   The base filesystem path for the module's files
 * webroot       string   The base URI for the module
 * icon          string   The URI for an icon to show in menus for the module
 * name          string   The name used menus and descriptions for a module
 * allow_guests  boolean  Allow guests, or only logged in users, access?
 * status        string   'inactive', 'hidden', 'notoolbar', or 'active'.
 *
 * The following attributes are only valid for the "horde" entry
 *
 * initial_page  string   The initial (default) page (filename) for the module
 * templates     string   The filesystem path to the templates directory
 *
 */

$this->applications['horde'] = array(
    'fileroot' => dirname(__FILE__) . '/..',
    'webroot' => '/horde',
    'initial_page' => 'chora/index.php',
    'icon' => '/horde/graphics/home.gif',
    'name' => _("Horde"),
    'allow_guests' => true,
    'status' => 'active',
    'templates' => dirname(__FILE__) . '/../templates',
    'cookie_domain' => $_SERVER['SERVER_NAME'],
    // ** If IE will be used to access Horde modules, you should read
    //    this discussion about the cookie_path setting (discussing issues
    //    with IE's Content Advisor):
    //    http://lists.horde.org/archives/imp/Week-of-Mon-20030113/029149.html
    'cookie_path' => '/horde',
    'server_name' => $_SERVER['SERVER_NAME'],
    'server_port' => $_SERVER['SERVER_PORT']
);
/*
$this->applications['logout'] = array(
    'fileroot' => dirname(__FILE__) . '/..',
    'webroot' => $this->applications['horde']['webroot'],
    'initial_page' => 'login.php?reason=logout',
    'icon' => $this->applications['horde']['webroot'] . '/graphics/logout.gif',
    'name' => _("Logout"),
    'allow_guests' => false,
    'status' => 'notoolbar'
);

$this->applications['imp'] = array(
    'fileroot' => dirname(__FILE__) . '/../imp',
    'webroot' => $this->applications['horde']['webroot'] . '/imp',
    'icon' => $this->applications['horde']['webroot'] . '/imp/graphics/imp.gif',
    'name' => _("Mail"),
    'allow_guests' => false,
    'status' => 'inactive'
);

$this->applications['ingo'] = array(
    'fileroot' => dirname(__FILE__) . '/../ingo',
    'webroot' => $this->applications['horde']['webroot'] . '/ingo',
    'icon' => $this->applications['horde']['webroot'] . '/ingo/graphics/ingo.gif',
    'name' => _("Filters"),
    'allow_guests' => false,
    'status' => 'inactive'
);

$this->applications['turba'] = array(
    'fileroot' => dirname(__FILE__) . '/../turba',
    'webroot' => $this->applications['horde']['webroot'] . '/turba',
    'icon' => $this->applications['horde']['webroot'] . '/turba/graphics/turba.gif',
    'name' => _("Address Book"),
    'allow_guests' => false,
    'status' => 'inactive'
);

$this->applications['kronolith'] = array(
    'fileroot' => dirname(__FILE__) . '/../kronolith',
    'webroot' => $this->applications['horde']['webroot'] . '/kronolith',
    'icon' => $this->applications['horde']['webroot'] . '/kronolith/graphics/kronolith.gif',
    'name' => _("Calendar"),
    'allow_guests' => false,
    'status' => 'inactive'
);

$this->applications['mnemo'] = array(
    'fileroot' => dirname(__FILE__) . '/../mnemo',
    'webroot' => $this->applications['horde']['webroot'] . '/mnemo',
    'icon' => $this->applications['horde']['webroot'] . '/mnemo/graphics/mnemo.gif',
    'name' => _("Memos"),
    'allow_guests' => false,
    'status' => 'inactive'
);

$this->applications['nag'] = array(
    'fileroot' => dirname(__FILE__) . '/../nag',
    'webroot' => $this->applications['horde']['webroot'] . '/nag',
    'icon' => $this->applications['horde']['webroot'] . '/nag/graphics/nag.gif',
    'name' => _("Tasks"),
    'allow_guests' => false,
    'status' => 'inactive'
);
*/
$this->applications['chora'] = array(
    'fileroot' => dirname(__FILE__) . '/../chora',
//    'fileroot' => dirname(__FILE__) . '/horde/chora',
    'webroot' =>  '/CVS',
    'icon' => '/CVS/graphics/chora.gif',
    'name' => _("CVS"),
    'allow_guests' => true,
    'status' => 'inactive'
);
/*
$this->applications['klutz'] = array(
    'fileroot' => dirname(__FILE__) . '/../klutz',
    'webroot' => $this->applications['horde']['webroot'] . '/klutz',
    'icon' => $this->applications['horde']['webroot'] . '/klutz/graphics/klutz.gif',
    'name' => _("Comics"),
    'allow_guests' => false,
    'status' => 'inactive'
);
*/

/**
 * Service registry
 * ----------------
 * The following entries register services for specific Horde
 * applications. Unless you need to customize your Horde
 * applications, you shouldn't touch this section.
 */

$this->services['imp']['auth']['login'] = array(
    'link' => '%application%/login.php?url=|url|'
);
$this->services['imp']['auth']['logout'] = array(
    'link' => '%application%/login.php?reason=logout&url=|url|'
);
$this->services['imp']['mail']['compose'] = array(
    'link' => "javascript:open_compose_win('popup=1&to=|to|&cc=|cc|&bcc=|bcc|&msg=|msg|&subject=|subject||extra|');",
    'includeFile' => '%application%/templates/javascript/open_compose_win.js',
);
$this->services['imp']['mail']['composePopup'] = array(
    'file' => '%application%/lib/api.php',
    'includeFile' => '%application%/templates/javascript/open_compose_win.js',
    'function' => 'impComposePopup',
    'args' => array('options')
);
$this->services['imp']['horde']['summary'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'impSummary',
    'args' => array(),
    'type' => 'string'
);

$this->services['turba']['contacts']['search'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaExpandAddresses',
    'args' => array('addresses', 'addressbooks', 'fields'),
    'type' => 'array'
);
$this->services['turba']['contacts']['add'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaAddAddress',
    'args' => array('name', 'address', 'addressbook'),
    'type' => 'boolean'
);
$this->services['turba']['contacts']['import_vcard'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaImportvCard',
    'args' => array('source', 'vcard_data'),
    'type' => 'array'
);
$this->services['turba']['contacts']['sources'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaGetSources',
    'args' => array('writeable'),
    'type' => 'array'
);
$this->services['turba']['contacts']['fields'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaGetFields',
    'args' => array('addressbook'),
    'type' => 'array'
);
$this->services['turba']['contacts']['add_field'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaAddField',
    'args' => array('address', 'name', 'field', 'value', 'addressbook'),
    'type' => 'array'
);
$this->services['turba']['contacts']['delete_field'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaDeleteField',
    'args' => array('address', 'field', 'addressbooks'),
    'type' => 'array'
);
$this->services['turba']['contacts']['get_field'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaGetField',
    'args' => array('address', 'field', 'addressbooks'),
    'type' => 'array'
);
$this->services['turba']['contacts']['list_field'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'turbaListField',
    'args' => array('field', 'addressbooks'),
    'type' => 'array'
);

$this->services['kronolith']['horde']['summary'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'kronolithSummary',
    'args' => array(),
    'type' => 'string'
);
$this->services['kronolith']['events']['show'] = array(
    'link' => '%application%/viewevent.php?eventID=|event|'
);

$this->services['mnemo']['memos']['list'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'mnemoListMemos',
    'args' => array('sortby', 'sortdir'),
    'type' => 'array'
);
$this->services['mnemo']['memos']['search'] = array(
    'link' => '%application%/search.php'
);
$this->services['mnemo']['memos']['show'] = array(
    'link' => '%application%/view.php?memo=|memo|'
);
$this->services['mnemo']['memos']['add'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'mnemoAddMemo',
    'args' => array('body', 'category'),
    'type' => 'integer'
);
$this->services['mnemo']['horde']['summary'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'mnemoSummary',
    'args' => array(),
    'type' => 'string'
);

$this->services['nag']['tasks']['list'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'nagListTasks',
    'args' => array('sortby', 'sortdir'),
    'type' => 'array'
);
$this->services['nag']['tasks']['search'] = array(
    'link' => '%application%/search.php'
);
$this->services['nag']['tasks']['show'] = array(
    'link' => '%application%/view.php?task=|task|'
);
$this->services['nag']['tasks']['add'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'nagAddTask',
    'args' => array('name', 'description', 'due'),
    'type' => 'integer'
);
$this->services['nag']['horde']['summary'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'nagSummary',
    'args' => array(),
    'type' => 'string'
);

$this->services['ingo']['filter']['blacklistFrom'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'blacklistFrom',
    'args' => array('addresses')
);
$this->services['ingo']['filter']['whitelistFrom'] = array(
    'file' => '%application%/lib/api.php',
    'function' => 'whitelistFrom',
    'args' => array('addresses')
);

$this->services['default']['mail']['compose'] = array(
    'link' => 'mailto:%to%'
);
