<?php
header('Content-Type: text/vnd.wap.wml');
echo '<?xml version="1.0" encoding="UTF-16"?>';
?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">
<wml>
<card title="<?php echo _("Horde") ?>">
<do type="accept">
    <go href="$(horde:noesc)" />
</do>

<p mode="nowrap">
<b><?php echo _("Horde Wireless") ?></b>

<select name="horde">
    <option value="login.php">
        Log In
    </option>
    <option value="turba/search.php">
        Directory Search
    </option>
</select>

</p>

</card>
</wml>
