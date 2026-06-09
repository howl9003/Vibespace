<?php
/***************************************************************************
                          config.php  -  description
                             -------------------
    begin                : Sat June 17 2000
    copyright            : (C) 2000 by James Atkinson
    email                : james@totalgeek.org

    $Id: config.php,v 1.3 2004/05/13 14:11:34 brian Exp $

 ***************************************************************************/

/***************************************************************************
 *                                         				                                
 *   This program is free software; you can redistribute it and/or modify  	
 *   it under the terms of the GNU General Public License as published by  
 *   the Free Software Foundation; either version 2 of the License, or	    	
 *   (at your option) any later version.
 *
 ***************************************************************************/

/* -- Let's Set A Few URL Paths -- */
// This first path is where you have phpBB installed
// If you site is http://www.mysite/phpBB leave this be.
// Otherwise set it to your forum path.
// Do not include the closeing / mark.
$url_phpbb = "/phpBB"; 
// You shouldn't have to change any of these 3.
$url_admin = "$url_phpbb/admin"; 
$url_images = "$url_phpbb/images"; 
$url_smiles = "$url_images/smiles";

/* -- Cookie settings (lastvisit, userid) -- */
// Most likely you can leave this be, however if you have problems
// logging into the forum set this to your domain name, without 
// the http://
// For example, if your forum is at http://www.mysite.com/phpBB then
// set this value to 
// $cookiedomain = "www.mysite.com";
$cookiedomain = "";
// It should be safe to leave this alone as well. But if you do change it
// make sure you don't set it to a variable already in use such as 'forum'.
$cookiename = "phpBB";
// It should be safe to leave these alone as well.
$cookiepath = $url_phpbb;
$cookiesecure = false;

/* -- Cookie settings (sessions) -- */
// This is the cookie name for the sessions cookie, you shouldn't have to change it
$sesscookiename = "phpBBsession";
// This is the number of seconds that a session lasts for, 3600 == 1 hour.
// The session will exprire if the user dosan't view a page on the forum within
// this amount of time.
$sesscookietime = 3600; 

/* -- You shouldn't have to change anything after this point */
/* Stuff for priv msgs - not in DB yet: */
$allow_pmsg_bbcode = 1;
$allow_pmsg_html = 1;

/* -- Cosmetic Settings -- */
$FontColor = "#FFFFFF";
$textcolorMessage = "#FFFFFF";  // Message Font Text Color
$FontSizeMessage = "1";  // Message Font Text Size
$FontFaceMessage = "Arial";  // Message Font Text Face

/* -- Images -- */
$reply_wquote_image = "$url_images/quote.gif";

$folder_image = "$url_images/folder.gif";
$hot_folder_image = "$url_images/hot_folder.gif";
$newposts_image = "$url_images/red_folder.gif";
$hot_newposts_image = "$url_images/hot_red_folder.gif";

$posticon = "$url_images/posticon.gif";
$edit_image = "$url_images/edit.gif";
$profile_image = "$url_images/profile.gif";
$email_image = "$url_images/email.gif";

$locked_image = "$url_images/lock.gif";
$locktopic_image = "$url_images/lock_topic.gif";
$deltopic_image = "$url_images/del_topic.gif";
$movetopic_image = "$url_images/move_topic.gif";
$unlocktopic_image = "$url_images/unlock_topic.gif";
$ip_image = "$url_images/ip_logged.gif";

$www_image = "$url_images/www_icon.gif";
$icq_add_image = "$url_images/icq_add.gif";
$images_aim = "$url_images/aim.gif";
$images_yim = "$url_images/yim.gif";
$images_msnm = "$url_images/msnm.gif";

/* -- Other Settings -- */
$phpbbversion = "1.2.0";
