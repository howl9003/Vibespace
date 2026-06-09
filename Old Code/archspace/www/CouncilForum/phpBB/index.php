<?php
/***************************************************************************
                          index.php  -  description
                             -------------------
    begin                : Sat June 17 2000
    copyright            : (C) 2000 by James Atkinson
    email                : james@totalgeek.org

    $Id: index.php,v 1.37 2001/02/09 19:47:33 thefinn Exp $

 ***************************************************************************/

/***************************************************************************
 *                                         				                                
 *   This program is free software; you can redistribute it and/or modify  	
 *   it under the terms of the GNU General Public License as published by  
 *   the Free Software Foundation; either version 2 of the License, or	    	
 *   (at your option) any later version.
 *
 ***************************************************************************/
include('extention.inc');
include('functions.'.$phpEx);
include('config.'.$phpEx);
require("auth.$phpEx");
$pagetitle = $l_indextitle;
$pagetype = "index";
include('page_header.'.$phpEx);

$sql = "SELECT * FROM catagories ORDER BY cat_order";
if(!$result = mysql_query($sql, $db))
	error_die("Unable to get categories from database");
?>

<TABLE BORDER="0" WIDTH="<?php echo $TableWidth?>" CELLPADDING="1" CELLSPACING="0" ALIGN="CENTER" VALIGN="TOP"><TR><TD BGCOLOR="<?php echo $table_bgcolor?>">
<TABLE BORDER="0" CELLPADDING="1" CELLSPACING="1" WIDTH="100%">
<TR BGCOLOR="<?php echo $color1?>" ALIGN="LEFT">
	<TD BGCOLOR="<?php echo $color1?>" ALIGN="CENTER" VALIGN="MIDDLE">&nbsp;</TD>
	<TD><FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize1?>" COLOR="<?php echo $textcolor?>"><B><?php echo $l_forum?></B></font></TD>
	<TD ALIGN="CENTER"><FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize1?>" COLOR="<?php echo $textcolor?>"><B><?php echo $l_topics?></B></font></TD>
	<TD ALIGN="CENTER"><FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize1?>" COLOR="<?php echo $textcolor?>"><B><?php echo $l_posts?></B></font></TD>
	<TD ALIGN="CENTER"><FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize1?>" COLOR="<?php echo $textcolor?>"><B><?php echo $l_lastpost?></B></font></TD>
	<TD ALIGN="CENTER"><FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize1?>" COLOR="<?php echo $textcolor?>"><B><?php echo $l_moderator?></B></font></TD>
</TR>

<?php
$row = @mysql_fetch_array($result);
do {
   if($viewcat && $row[cat_id] == $viewcat) {
	 $sub_sql = "SELECT f.* FROM forums f WHERE f.cat_id = '$row[cat_id]' ORDER BY forum_id";
   }
   else if($viewcat && $row[cat_id] != $viewcat) {
      $title = stripslashes($row[cat_title]);
      echo "<TR ALIGN=\"LEFT\" VALIGN=\"TOP\"><TD COLSPAN=6 BGCOLOR=\"$color1\"><FONT FACE=\"$FontFace\" SIZE=\"$FontSize2\" COLOR=\"$textcolor\"><B><a href=\"$PHP_SELF?viewcat=$row[cat_id]\">$title</a></B></FONT></TD></TR>";
      continue;
   }
   else if(!$viewcat) {
      $sub_sql = "SELECT f.* FROM forums f WHERE f.cat_id = '$row[cat_id]' ORDER BY forum_id";
   }
   
      if(!$sub_result = mysql_query($sub_sql, $db)){
			error_die("Couldn't get forum list from database");
		}
      if($myrow = mysql_fetch_array($sub_result)) {
	 $title = stripslashes($row[cat_title]);
	 echo "<TR ALIGN=\"LEFT\" VALIGN=\"TOP\"><TD COLSPAN=6 BGCOLOR=\"$color1\"><FONT FACE=\"$FontFace\" SIZE=\"$FontSize2\" COLOR=\"$textcolor\"><B><a href=\"$PHP_SELF?viewcat=$row[cat_id]\">$title</a></B></FONT></TD></TR>";
	 do {
	    $last_post = get_last_post($myrow[forum_id], $db, "forum");
	    list($last_post_datetime, $null) = split("by", $last_post);
	    list($last_post_date, $last_post_time) = split(" ", $last_post_datetime);
	    list($year, $month, $day) = explode("-", $last_post_date);
	    list($hour, $min) = explode(":", $last_post_time);
	    $last_post_time = mktime($hour, $min, 0, $month, $day, $year);
	    
	    echo "<TR  ALIGN=\"LEFT\" VALIGN=\"TOP\">";
	    $total_topics = get_total_topics($myrow[forum_id], $db);
	    //if((($last_visit - $last_post_time) < 600) && $last_post != "No posts") {
 		  if($last_post_time > $last_visit && $last_post != "No posts") {
			  echo "<TD BGCOLOR=\"$color1\" ALIGN=\"CENTER\" VALIGN=\"middle\" WIDTH=5%><IMG SRC=\"$newposts_image\"></TD>";
		  }
	    else {
	       echo "<TD BGCOLOR=\"$color1\" ALIGN=\"CENTER\" VALIGN=\"middle\" WIDTH=5%><IMG SRC=\"$folder_image\"></TD>";
	    }
	    $name = stripslashes($myrow[forum_name]);
	    $total_posts = get_total_posts($myrow[forum_id], $db, "forum");
	    echo "<TD BGCOLOR=\"$color2\"><FONT FACE=\"$FontFace\" SIZE=\"$FontSize2\" COLOR=\"$textcolor\"><a href=\"viewforum.$phpEx?forum=$myrow[forum_id]&$total_posts\">$name</a></font>\n";
	    $desc = stripslashes($myrow[forum_desc]);
	    echo "<br><FONT FACE=\"$FontFace\" SIZE=\"$FontSize1\" COLOR=\"$textcolor\">$desc</font></TD>\n";
	    echo "<TD BGCOLOR=\"$color1\" WIDTH=5% ALIGN=\"CENTER\" VALIGN=\"MIDDLE\"><FONT FACE=\"$FontFace\" SIZE=\"$FontSize2\" COLOR=\"$textcolor\">$total_topics</font></TD>\n";
	    echo "<TD BGCOLOR=\"$color2\" WIDTH=5% ALIGN=\"CENTER\" VALIGN=\"MIDDLE\"><FONT FACE=\"$FontFace\" SIZE=\"$FontSize2\" COLOR=\"$textcolor\">$total_posts</font></TD>\n";
	    echo "<TD BGCOLOR=\"$color1\" WIDTH=15% ALIGN=\"CENTER\" VALIGN=\"MIDDLE\"><FONT FACE=\"$FontFace\" SIZE=\"$FontSize1\" COLOR=\"$textcolor\">$last_post</font></TD>\n";
	    $forum_moderators = get_moderators($myrow[forum_id], $db);
	    echo "<TD BGCOLOR=\"$color2\" WIDTH=5% ALIGN=\"CENTER\" VALIGN=\"MIDDLE\" NOWRAP><FONT FACE=\"$FontFace\" SIZE=\"-2\" COLOR=\"$textcolor\">";
	    $count = 0;
	    while(list($null, $mods) = each($forum_moderators)) {
	       while(list($mod_id, $mod_name) = each($mods)) {
		  if($count > 0)
		    echo ", ";
		  if(!($count % 2) && $count != 0)
		    echo "<BR>";
		  echo "<a href=\"bb_profile.$phpEx?mode=view&user=$mod_id\">$mod_name</a>";
		  $count++;
	       }
	    }
	    echo "</font></td></tr>\n";
	 } while($myrow = mysql_fetch_array($sub_result));
      }
} while($row = mysql_fetch_array($result));

?>
     </TABLE></TD></TR></TABLE>
<TABLE ALIGN="CENTER" BORDER="0" WIDTH="<?php echo $TableWidth?>"><TR><TD>
<FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize1?>" COLOR="<?php echo $textcolor?>">
<IMG SRC="<?php echo $newposts_image?>"> = <?php echo $l_newposts?>.
<BR><IMG SRC="<?php echo $folder_image?>"> = <?php echo $l_nonewposts?>.
</FONT></TD></TR></TABLE>

<?php
require('page_tail.'.$phpEx);
?>

