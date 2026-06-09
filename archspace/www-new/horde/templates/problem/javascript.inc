<script language="JavaScript" type="text/javascript">
<!--
// Check all of the main form fields to make sure they are filled in
function validate()
{
    if (document.problem_report.subject.value == '') {
        alert('<?php echo _("Please provide a summary of the problem."); ?>');
        document.problem_report.subject.focus();
        return false;
    } else if (document.problem_report.message.value == '') {
        alert('<?php echo _("You must describe the problem before you can send the problem report."); ?>');
        document.problem_report.message.focus();
        return false;
    }

    document.problem_report.actionID.value = <?php echo HORDE_SEND_PROBLEM_REPORT ?>;
    return true;
}
//-->
</script>
