<script language="JavaScript" type="text/javascript">
<!--
function open_help_win(module, topic)
{
    var win_location;
    var screen_width, screen_height;
    var win_top, win_left;
    var HelpWin;

    screen_height = 0;
    screen_width = 0;
    win_top = 0;
    win_left = 0;

    var help_win_width = 300;
    var help_win_height = 300;

    if (window.innerWidth) screen_width = window.innerWidth;
    if (window.innerHeight) screen_height = window.innerHeight;

    win_location = '<?php echo Horde::url($registry->getParam('webroot', 'horde') . '/help.php?1=1', true) ?>';
    if (topic == null) {
        win_location += '&module=' + module;
    } else if (topic == "") {
        win_location += '&module=' + module + '&show=topics';
    } else {
        win_location += '&module=' + module + '&topic=' + topic;
    }

    win_top  = screen_height - help_win_height - 20;
    win_left = screen_width  - help_win_width  - 20;
    HelpWin  = window.open(
        win_location,
        'HelpWindow',
        'width='+help_win_width+',height='+help_win_height+',top='+win_top+',left='+win_left
    );
    HelpWin.focus();
}
//-->
</script>
