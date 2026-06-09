<?php
// $Horde: chora/config/prefs.php.dist,v 1.1.2.1 2002/03/20 20:30:06 chuck Exp $

$prefGroups['logintasks'] = array(
    'column' => _("Other Options"),
    'label' => _("Login Tasks"),
    'desc' => _("Customize tasks to run upon logging in to Chora."),
    'members' => array('remember_last_file')
);

// last browse file/directory
$_prefs['last_file'] = array(
    'value' => 0,
    'locked' => false,
    'shared' => false,
    'type' => 'implicit'
);

// last cvsroot used
$_prefs['last_cvsroot'] = array(
    'value' => 0,
    'locked' => false,
    'shared' => false,
    'type' => 'implicit'
);

// show the last login time of user
$_prefs['remember_last_file'] = array(
    'value' => 1,
    'locked' => false,
    'shared' => false,
    'type' => 'checkbox',
    'desc' => _("Use last viewed file or directory at login time")
);
