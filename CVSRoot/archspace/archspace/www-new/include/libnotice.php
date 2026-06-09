<?
$NEWS_FORUM=1;

if (!$libnotice_include)
{
	$libnotice_include = true;
	include "libstd.php";
	
	// Retrieves and displays $row rows from the database
	function notice($row)
	{
	  global $NEWS_FORUM;
	  create_db_connection("Archspace");
          $res = mysql_query("SELECT topic_id, topic_title, topic_time FROM asbb_topics WHERE forum_id=$NEWS_FORUM ORDER 
BY topic_id DESC LIMIT $row");
	  
	  if (!$res || mysql_num_rows($res) == 0)
          {
      		echo "<TR><TD VALIGN=\"TOP\" CLASS=maintext><FONT COLOR=#666666 face='Verdana, Arial, Helvetica, sans-serif' size=2>No News Entrys</TD></TR>";
      		return;
    	  }

	  while (list($id, $topictitle, $topicdate) = mysql_fetch_row($res) )
	  {
	   $topicdate = date('M j/y', $topicdate);
	   $iconhtml = "";
	   echo "<TR>";
	   echo "  <TD VALIGN=\"TOP\" CLASS=\"maintext\" WIDTH=75 align=left>&nbsp;<FONT COLOR=#666666 face='Verdana, Arial, Helvetica, sans-serif' 
size=2>$topicdate</FONT></TD>";
	   echo "  <TD VALIGN=\"TOP\" CLASS=\"maintext\" align=left width=4> <!-- was icon html --> </TD>";
	   echo "  <TD VALIGN=\"TOP\" CLASS=\"maintext\" ALIGN=left width=215><FONT COLOR=#666666 face='Verdana, Arial, 
Helvetica, sans-serif' size=2><A HREF=/board/viewtopic.php?t=$id>$topictitle</A></FONT></TD>";
	   echo "</TR>";
	  }
	  mysql_free_result($res);
	}

	// Retrieves and displays ALL entries from the database
	function all()
	{
   	  global $NEWS_FORUM;

   	  create_db_connection("Archspace");
	  $res = mysql_query("SELECT topic_id, topic_title, topic_time FROM asbb_topics WHERE forum_id=$NEWS_FORUM ORDER BY topic_id DESC LIMIT 50");

  	  if (!$res || mysql_num_rows($res) == 0)
	  {
        	echo "<TR><TD VALIGN=\"TOP\" ALIGN=\"CENTER\" CLASS=maintext><FONT COLOR=66666 face='Verdana, Arial, Helvetica, sans-serif' size=2>No News Entrys</TD></TR>";
	        return;
      	  }
	
	  while (list($id,$topictitle,$topicdate) = mysql_fetch_row($res))
	   {
	      // get details about the selected post id
              $topicdate = date('M j Y - h:i A', $topicdate);

//	      if ($topicinfo['icon_id'] != "0")
//		$iconhtml = "<IMG SRC=".$SDK->board_url."/style_images/".$skininfo['img_dir']."/icon".$topicinfo['icon_id'].".gif BORDER=0>";
//	      else
        	$iconhtml = "&nbsp;";


	      echo "<TR>"; 
	      echo "  <TD CLASS='maintext' align=left><FONT COLOR='666666' face='Verdana, Arial, Helvetica, sans-serif' size=2>$topicdate</FONT></TD>";
	      echo "  <TD VALIGN=\"TOP\" CLASS=maintext>$iconhtml</TD>";
	      echo "  <TD CLASS='maintext' align=left><FONT face='Verdana, Arial, Helvetica, sans-serif' size=2><A HREF=/board/viewtopic.php?t=$id>".$topictitle."</A></FONT></TD>";
	      echo "</TR>";
	   }
	  mysql_free_result($res);
	}
}
?>
