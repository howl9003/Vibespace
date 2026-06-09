<p>
Username:<input type="text" name="u" format="*m"/>
Password:<input type="password" name="p" format="*m"/>
<anchor title="Login">Login
  <go href="<?php echo Horde::pselfUrl() ?>" method="post">
  <postfield name="horde_user" value="$(u)"/>
  <postfield name="horde_pass" value="$(p)"/>
  <postfield name="url" value="<?php echo Horde::getFormData('url', '') ?>"/>
  </go>
</anchor>
</p>
