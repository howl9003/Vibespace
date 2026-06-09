<?php
// Simple script to set correct charset for changelog
/* $Id: changelog.php,v 1.1.1.1 2004/12/25 09:14:36 brian Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

header('Content-type: text/plain; charset=utf-8');
readfile('ChangeLog');
?>
