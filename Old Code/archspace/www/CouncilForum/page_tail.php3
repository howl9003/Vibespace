<?php
if($user_logged_in && $userdata[user_level] == 4) {
?>
     <FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize3?>" COLOR="<?php echo $textcolor?>">
     <CENTER><a href="<?php echo $url_admin?>"><?php echo $l_adminpanel?></a></CENTER><BR>
     </FONT>
<?php
}
?>
<FONT FACE="<?php echo $FontFace?>" SIZE="<?php echo $FontSize3?>" COLOR="<?php echo $textcolor?>">
<?php
  /* Please Note!
   * This is a notice to anyone who is using phpBB and altering this file.
   * PLEASE do not remove the following copyright notice. All we ask in return
   * for the use of this software is that you leave the copyright notice on here.
   * Credit where credit is due, alot of people have put alot of hard work
   * into this. :)
   * Thank you.
   * - The phpBB Group
   */
?>
</font><BR>

<?php
showfooter($db);
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
//printf("<center><font size=-2>phpBB Created this page in %f seconds.</font></center>", $totaltime);
?>

</FONT>
</BODY>
</HTML>
