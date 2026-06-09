<a name="rev<?php echo $rev ?>" />
<table width="100%" cellspacing="0" cellpadding="3" class="diff-back">
<tr class="diff-back"><td width="100%"> 
<table width="100%" cellspacing="0" cellpadding="4" class="diff-back">
<tr class="<?php echo $bgclass ?>"><td align="left">
<a href="<?php echo $textURL ?>"><span class="title"><?php echo $rev ?></span></a> <?php echo sprintf(_("by %s"), $author) ?>
</td><td align="right">
<a href="<?php echo Chora::url('annotate', $where, array('rev' => $rev)); ?>"><?php echo _("Annotate") ?></a> <b>|</b>
<a href="<?php echo Chora::url('co', $where, array('r' => $rev)); ?>"><?php echo _("View") ?></a> <b>|</b>
<a href="<?php echo Chora::url('co', $where, array('r' => $rev, 'p' => 1)); ?>"><?php echo _("Download") ?></a>
</td></tr>
<tr valign="top" class="diff-back">
<td width="35%">
<?php echo $commitDate ?> <i>(<?php echo sprintf(_("%s ago"), $readableDate) ?>)</i><br />
<?php if (!empty($branchName)): ?>
<?php echo _("Branch:") ?> <b><a href="<?php echo Chora::url('cvs', $where, array('onb' => $branchRev)) ?>"><?php echo $branchName ?></a></b><br />
<?php endif; ?>
<?php if (!empty($commitTags)): ?>
<?php echo _("CVS Tags:") ?> <b><?php echo $commitTags ?></b>
<br />
<?php endif; ?>
<?php if (!empty($branchPoints)): ?>
<?php echo _("Branch Point for:") ?> <b><?php echo $branchPoints ?></b>
<br />
<?php endif; ?>
<?php if (!empty($prevRevision)): ?>
<?php echo sprintf(_("Changed since <b>%s</b>"), $prevRevision) ?></b>: <?php echo $changedLines ?>
<?php endif; ?>
<?php if ($manyRevisions): ?>
<?php if ($r1 === $rev): ?>
<b>[<a href="<?php echo Chora::url('cvs', $where) ?>"><?php echo _("Unselect") ?></a>]</b>
<?php else: ?>
[<a href="<?php echo $selCvsURL ?>"><?php echo _("Select for Diff")?></a>]
<?php endif; ?>
<?php endif; ?>
<br />
<?php if (!empty($prevRevision)): ?>
<a href="<?php echo $diffURL ?>"><?php echo sprintf(_("Diffs to version %s"), $prevRevision) ?></a>
(<a href="<?php echo $longDiffURL ?>"><?php echo _("long") ?></a> /
<a href="<?php echo $uniDiffURL ?>"><?php echo _("unified") ?></a>)
<br />
<a href="<?php echo $nowsDiffURL ?>"><?php echo sprintf(_("Diffs ignoring whitespace to version %s"), $prevRevision) ?></a>
(<a href="<?php echo $nowsLongDiffURL ?>"><?php echo _("long") ?></a> /
<a href="<?php echo $nowsUniDiffURL ?>"><?php echo _("unified") ?></a>)
<br />
<?php endif; ?>
<?php if ($manyRevisions && !empty($r1) && ($r1 != $prevRevision) && ($r1 != $rev)): ?>
<a href="<?php echo $selDiffURL ?>"><?php echo sprintf(_("Diffs to selected version %s"), $r1) ?></a>
(<a href="<?php echo $selLongDiffURL ?>"><?php echo _("long") ?></a> /
<a href="<?php echo $selUniDiffURL ?>"><?php echo _("unified") ?></a>)
<br />
<?php endif; ?>
</td><td class="diff-log" width="65%">
<?php echo $logMessage ?>
</td></tr>
</table>
</td></tr>
</table>
