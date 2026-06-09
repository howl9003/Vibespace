<table width="100%" cellspacing="1" cellpadding="4" border="0">
<tr class="smallheader"><td>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr class="smallheader" valign="middle">
<td align="left" class="smallheader">
<span class="title"><?php echo $title ?></span>
</td>
<td align="right">
<table border="0" cellspacing="0" cellpadding="0"><tr><td align="right">
<a href="http://horde.org/chora/" class="smallheader">
 <?php echo Horde::img('chora.gif', 'alt="' . _("Chora Homepage") . '" width="16" height="16"'); ?></a>
 &nbsp;
</td>
<td align="left">
<a href="http://horde.org/chora/" class="smallheader">CHORA <?php echo CHORA_VERSION ?></a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td></tr>
<tr class="menufoot"><td>
<table width="100%" cellspacing="0" cellpadding="1" border="0">
<tr><td>
<?php echo _("Location:") ?> <b>
[ <a href="<?php echo Chora::url('cvs') ?>"><?php echo $conf['options']['cvsRootName'] ?></a> ]
<?php echo Chora::whereMenu() ?>

</b>
<?php if (isset($onb) && $onb): ?>
&nbsp; &nbsp; <i>(<?php echo _("Tracking Branch") ?> <b><?php echo $fl->branches[$onb] ?></b>)</i>
<?php endif; ?>
<?php if ($where == '' && sizeof($cvsroots) > 1): ?>
&nbsp; &nbsp;
(<?php echo Chora::repositories() ?>)
<?php endif; ?>
</td>
<?php if (!empty($extraLink)): ?>
<td align="right">
<?php echo $extraLink ?>
</td>
<?php endif; ?>
</tr>
</table>
</td></tr>
<?php if ($where == '' && @is_file($conf['paths']['introText'])): ?>
<tr class="info">
<td>
<?php @readfile($conf['paths']['introText']) ?>
</td>
</tr>
<?php endif ?>

</table>
