<?php
// $Horde: horde/config/prefs.php.dist,v 1.7.2.9 2002/10/29 21:17:25 chuck Exp $

$prefGroups['language'] = array(
    'column' => _("Your Information"),
    'label' => _("Language"),
    'desc' => _("Set your preferred display language."),
    'members' => array('language'));

$prefGroups['display'] = array(
    'column' => _("Other Information"),
    'label' => _("Display Options"),
    'desc' => _("Set your page refreshing, and other display options."),
    'members' => array('summary_columns', 'summary_refresh_time', 'menu_view', 'initial_application'));


// user language
$_prefs['language'] = array(
    'value' => '',
    'locked' => false,
    'shared' => true,
    'type' => 'select',
    'desc' => _("Select your preferred language:"));

$_prefs['summary_columns'] = array(
    'value' => 3,
    'locked' => false,
    'shared' => false,
    'type' => 'number',
    'desc' => _("Number of columns in the summary view:"));

$_prefs['summary_refresh_time'] = array(
    'value' => 300,
    'locked' => false,
    'shared' => false,
    'type' => 'enum',
    'enum' => array(0 => _("Never"),
                    30 => _("Every 30 seconds"),
                    60 => _("Every minute"),
                    300 => _("Every 5 minutes"),
                    900 => _("Every 15 minutes"),
                    1800 => _("Every half hour")),
    'desc' => _("Refresh Summary View:"));

$_prefs['menu_view'] = array(
    'value' => 'both',
    'locked' => false,
    'shared' => true,
    'type' => 'enum',
    'enum' => array('text' => _("Text Only"),
                    'icon' => _("Icons Only"),
                    'both' => _("Icons with text")),
    'desc' => _("Menu mode:"));

// what application should we go to after login?
$_prefs['initial_application'] = array(
    'value' => 'chora',
    'locked' => true,
    'shared' => true,
    'type' => 'select',
    'desc' => _("What application should Horde display after login?"));

// confirm when doing maintenance operations? If false (0), they will
// be performed with no input from/check with the user.
$_prefs['confirm_maintenance'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => true,
    'type' => 'checkbox',
    'desc' => _("Ask for confirmation before doing maintenance operations?"));
