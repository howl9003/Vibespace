<?php
/**
 * theme.php — original-Archspace look for the auth pages.
 *
 * Wraps the modern email/password forms in the game's 2004 styling: the
 * original CSS (archspace.css / cssLib.css), the mainimg.gif hero, the dark
 * "#252525" form box, the white underline-only ".newInput" fields, and the
 * original button GIFs (bu_login / bu_register / bu_ok / bu_reset / bu_back).
 *
 * Usage:
 *   auth_page_start('Sign In');
 *     ... echo auth_error($e); ...
 *     <form ...> ... auth_input(...) ... auth_submit('bu_login.gif','login') ...
 *   auth_page_end($footerHtml);
 */

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Page head + hero + opening of the centered dark form box. */
function auth_page_start(string $title): void {
    $t = h($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2">
<title>Archspace &mdash; {$t}</title>
<link rel="stylesheet" href="/archspace.css">
<link rel="stylesheet" href="/cssLib.css">
<style>
  /* Plain black background (just the logo), bold serif to match the original
     login/register button GIFs (Times New Roman Bold). */
  html,body{margin:0;background:#000;
            font-family:"Times New Roman","Liberation Serif",Georgia,serif;
            font-size:14px;color:#bbb;}
  .as-auth-wrap{min-height:100vh;display:flex;align-items:flex-start;justify-content:center;
                padding:40px 12px;}
  .as-auth-col{width:486px;max-width:100%;text-align:center;}
  .as-hero{margin:0 auto 14px;max-width:100%;height:auto;border:0;display:block;}
  .as-box{display:inline-block;background:#252525;padding:16px 20px;text-align:left;
          min-width:240px;}
  .as-box .lbl{color:#fff;font-weight:bold;display:block;margin:6px 0 2px;}
  /* The original .newInput uses an invalid "background:clean" (renders as a
     white box -> invisible white text). Force the intended look: white text on
     a transparent field with a white underline, on the dark #252525 box. */
  .as-box .newInput{width:200px;max-width:100%;font-family:inherit;font-size:13px;
                    background:transparent;color:#fff;outline:none;}
  .as-title{color:#fff;font-size:15px;font-weight:bold;letter-spacing:.04em;margin:0 0 10px;}
  /* login + register on one line, then a small "forgot" link under them */
  .as-btnrow{display:flex;gap:14px;align-items:center;justify-content:center;margin-top:14px;}
  .as-btnrow a{line-height:0;}
  .as-forgot-sm{margin-top:8px;text-align:center;}
  .as-forgot-sm a{color:#B5721E;text-decoration:none;font-size:12px;}
  .as-forgot-sm a:hover{text-decoration:underline;}
  .as-msg-error{background:#2a0a0a;border-left:3px solid #c0392b;color:#e88;
                padding:6px 10px;margin:0 0 10px;}
  .as-msg-ok{background:#0a2a14;border-left:3px solid #2e8b57;color:#9d9;
             padding:6px 10px;margin:0 0 10px;}
  .as-btn{background:none;border:0;padding:0;cursor:pointer;margin-top:12px;}
  .as-links{margin-top:14px;font-size:12px;line-height:1.9;}
  .as-links a{color:#a9a9a9;text-decoration:underline;}
  .as-links a:hover{color:#f0ffff;}
  .as-forgot a{color:#B5721E;text-decoration:none;}
  .as-forgot a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="as-auth-wrap"><div class="as-auth-col">
  <img class="as-hero" src="/image/as_game/menu_main.gif" width="170" height="119" alt="Archspace">
  <div class="as-box">
HTML;
}

/** Close the box + optional footer links, end the page. */
function auth_page_end(string $footerHtml = ''): void {
    echo "</div>\n";
    if ($footerHtml !== '') {
        echo '<div class="as-links">' . $footerHtml . "</div>\n";
    }
    echo "</div></div>\n</body>\n</html>";
}

function auth_title(string $t): string { return '<p class="as-title">' . h($t) . '</p>'; }
function auth_error(string $m): string { return $m === '' ? '' : '<div class="as-msg-error">' . h($m) . '</div>'; }
function auth_ok(string $m): string    { return $m === '' ? '' : '<div class="as-msg-ok">' . h($m) . '</div>'; }

/** A white label + underline-only (.newInput) field, matching the original. */
function auth_input(string $label, string $name, string $type = 'text',
                    string $value = '', string $autocomplete = ''): string {
    $ac = $autocomplete !== '' ? ' autocomplete="' . h($autocomplete) . '"' : '';
    return '<label class="lbl">' . h($label) . '</label>'
         . '<input class="newInput" type="' . h($type) . '" name="' . h($name) . '"'
         . ' value="' . h($value) . '" maxlength="190" required' . $ac . '>';
}

/** An original button GIF used as the form submit. */
function auth_submit(string $gif, string $alt, int $w = 120, int $h = 16): string {
    return '<div><input class="as-btn" type="image" src="/image/as_login/' . h($gif) . '"'
         . ' width="' . $w . '" height="' . $h . '" alt="' . h($alt) . '"></div>';
}
