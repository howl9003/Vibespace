<form method="post" action="<?php echo $maint->getTarget() ?>" name="maint_confirm">
<?php echo $maint->getFormVars() ?>
<input type="hidden" name="confirm_maintenance" value="1" />
<table border="0" align="center" width="95%" cellpadding="3" cellspacing="2">
<tr class="header">
    <td class="header">
        <b><?php printf(_("%s Maintenance Operations - Confirmation"), $registry->getParam('name')); ?></b>
    </td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
    <td class="light">
        <span class="header"><?php echo _("Notice") ?></span>
        <span class="smallheader"><?php printf(_(" &#151; %s is ready to perform the maintenance operations checked below."), $registry->getParam('name')); ?>
        <br />
        <?php echo _("Check the box for any operation(s) you want to perform at this time.") ?></span>
    </td>
</tr>
<tr><td>&nbsp;</td></tr>
