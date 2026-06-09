<?php
// $Horde: horde/config/mime_drivers.php.dist,v 1.11.2.29 2003/12/19 10:00:19 jan Exp $

/**
 * Decide which output drivers you want to activate.
 * Right now, the choices are:
 *
 * php:          The internal PHP4 syntax highlighting engine
 * enscript:     GNU enscript
 * tgz:          Tarballs, including gzipped ones.
 * rar:          RAR archives
 * msword:       Microsoft Word files via wvHtml
 * msexcel:      Microsoft Excel files via xlhtml
 * mspowerpoint: Microsoft Powerpoint files via ppthtml
 * vcard:        vCards
 * zip:          Zip files
 * rpm:          RPM packages
 * deb:          Debian packages
 * enriched:     Enriched text format
 * images:       Image files
 */

$mime_drivers_map['horde']['registered'] = array(
    'php', 'tgz', 'vcard', 'enriched', 'images'
    // ,'msword', 'msexcel', 'mspowerpoint'
    // ,'enscript', 'rar', 'zip', 'rpm', 'deb'
    );


/**
 * If you want to specifically override any MIME type to be
 * handled by a specific driver, then enter it here.  Normally,
 * this is safe to leave, but it's useful when multiple drivers
 * handle the same MIME type, and you want to specify exactly
 * which one should handle it.
 */

$mime_drivers_map['horde']['overrides'] = array();


/**
 * Driver specific settings.  Here, you have to configure each
 * driver which you chose to activate above.  Default settings have
 * been filled in for them, and if you haven't activated it, then
 * just leave it as it is - it won't get loaded.
 *
 * The 'handles' setting below shouldn't be changed in most
 * circumstances.  It registers a set of MIME type that the driver
 * can handle.  The 'x-extension' MIME type is a special one to
 * Horde that maps a file extension to a MIME type.  It's useful
 * when you know that all files ending in '.c' are C files, for
 * example. You can set the MIME subtype to '*' to match all possible
 * subtypes (i.e. 'image/*').

 *
 * The 'icons' entry is for the driver to register various icons
 * for the MIME types it handles. The array consists of a
 * 'default' icon for that driver, and can also include specific
 * MIME-types which can have their own icons. You can set the MIME
 * subtype to '*' to match all possible subtypes (i.e. 'image/*').
 *
 */

/**
 * Default driver settings
 */

$mime_drivers['horde']['default']['icons'] = array(
        'default'                       => 'text.gif',
        'application/x-gzip'            => 'compressed.gif',
        'application/pdf'               => 'pdf.gif',
        'application/pgp-signature'     => 'encryption.gif',
        'application/x-pkcs7-signature' => 'encryption.gif',
        'application/octet-stream'      => 'binary.gif',
        'audio/basic'                   => 'audio.gif',
        'audio/x-sun'                   => 'audio.gif',
        'message/delivery-status'       => 'mail.gif',
        'message/rfc822'                => 'mail.gif',
        'unknown/octet-stream'          => 'binary.gif',
        'video/avi'                     => 'video.gif',
        'video/mpeg'                    => 'video.gif',
        'video/mpg'                     => 'video.gif');


/**
 * PHP driver settings
 */

$mime_drivers['horde']['php']['handles'] = array(
    'x-extension/php', 'x-extension/php3', 'x-extension/phps',
    'x-extension/php3s', 'application/x-httpd-php',
    'application/x-httpd-php3', 'application/x-httpd-phps');
$mime_drivers['horde']['php']['icons'] = array(
    'default' => 'php.gif');


/**
 * Enriched text driver settings
 */

$mime_drivers['horde']['enriched']['inline'] = true;
$mime_drivers['horde']['enriched']['handles'] = array(
    'text/enriched');
$mime_drivers['horde']['enriched']['icons'] = array(
    'default' => 'text.gif');


/**
 * GNU Enscript driver settings
 * Uncomment these lines to use this driver.
 */

/* Location of the enscript binary. */
// $mime_drivers['horde']['enscript']['location'] = '/usr/bin/enscript';
// $mime_drivers['horde']['enscript']['inline'] = false;
// $mime_drivers['horde']['enscript']['handles'] = array(
//     'text/html', 'x-extension/pl', 'x-extension/c',
//     'text/xml', 'application/x-sh', 'application/x-javascript',
//     'x-extension/java', 'x-extension/h', 'x-extension/cpp',
//     'x-extension/vhd', 'x-extension/vhdl', 'x-extension/sql',
//     'x-extension/vb', 'x-extension/vba', 'x-extension/el');
// $mime_drivers['horde']['enscript']['icons'] = array(
//     'default'                  => 'text.gif',
//     'text/html'                => 'html.gif',
//     'text/xml'                 => 'xml.gif',
//     'x-extension/c'            => 'source-c.gif',
//     'x-extension/h'            => 'source-h.gif',
//     'x-extension/java'         => 'source-java.gif',
//     'application/x-javascript' => 'script-js.gif');


/**
 * Tar driver settings
 */

/* Location of the tar binary. */
$mime_drivers['horde']['tgz']['location'] = '/bin/tar';
$mime_drivers['horde']['tgz']['inline'] = true;
$mime_drivers['horde']['tgz']['handles'] = array(
    'x-extension/tgz',
    'x-extension/tar',
    'application/x-gzip-compressed',
    'application/x-gtar',
    'application/x-tar');
$mime_drivers['horde']['tgz']['icons'] = array(
    'default' => 'compressed.gif');


/**
 * Zip file driver settings
 * Uncomment these lines to use this driver.
 */

/* Location of the zipinfo binary. */
// $mime_drivers['horde']['zip']['location'] = '/usr/bin/zipinfo';
// $mime_drivers['horde']['zip']['inline'] = true;
// $mime_drivers['horde']['zip']['handles'] = array(
//     'x-extension/zip',
//     'application/x-compressed',
//     'application/x-zip-compressed');
// $mime_drivers['horde']['zip']['icons'] = array(
//     'default' => 'compressed.gif');


/**
 * RAR archive driver settings
 * Uncomment these lines to use this driver.
 */

/* Location of the rar binary. */
// $mime_drivers['horde']['rar']['location'] = '/usr/bin/rar';
// $mime_drivers['horde']['rar']['inline'] = true;
// $mime_drivers['horde']['rar']['handles'] = array(
//     'x-extension/rar',
//     'application/x-rar-compressed');
// $mime_drivers['horde']['rar']['icons'] = array(
//     'default' => 'compressed.gif');


/**
 * MS Word driver settings
 * This driver requires wvWare (wvware.sourceforge.net) to be installed.
 * Uncomment these lines to use this driver.
 */

/* Location of the wvHtml binary. */
// $mime_drivers['horde']['msword']['location'] = '/usr/bin/wvHtml';
// $mime_drivers['horde']['msword']['inline'] = true;
// $mime_drivers['horde']['msword']['handles'] = array(
//     'application/msword',
//     'text/rtf',
//     'x-extension/doc',
//     'x-extension/rtf');
// $mime_drivers['horde']['msword']['icons'] = array(
//     'default' => 'msword.gif');


/**
 * MS Excel driver settings
 * This driver requires xlhtml to be installed.
 * xlhtml homepage: http://chicago.sourceforge.net/xlhtml/
 * Uncomment these lines to use this driver.
 */

/* Location of the xlhtml binary. */
// $mime_drivers['horde']['msexcel']['location'] = '/usr/local/bin/xlhtml';
// $mime_drivers['horde']['msexcel']['inline'] = false;
// $mime_drivers['horde']['msexcel']['handles'] = array(
//     'application/vnd.ms-excel',
//     'application/msexcel',
//     'x-extension/xls');
// $mime_drivers['horde']['msexcel']['icons'] = array(
//     'default' => 'msexcel.gif');


/**
 * MS Powerpoint driver settings
 * This driver requires ppthtml, included with xlhtml, to be installed.
 * xlhtml homepage: http://chicago.sourceforge.net/xlhtml/
 * Uncomment these lines to use this driver.
 */

/* Location of the ppthtml binary. */
// $mime_drivers['horde']['mspowerpoint']['location'] = '/usr/local/bin/ppthtml';
// $mime_drivers['horde']['mspowerpoint']['inline'] = false;
// $mime_drivers['horde']['mspowerpoint']['handles'] = array(
//     'application/vnd.ms-powerpoint',
//     'application/mspowerpoint',
//     'x-extension/ppt');
// $mime_drivers['horde']['mspowerpoint']['icons'] = array(
//     'default' => 'mspowerpoint.gif');


/**
 * vCard driver settings
 */

$mime_drivers['horde']['vcard']['handles'] = array(
    'text/x-vcard',
    'x-extension/vcf');
$mime_drivers['horde']['vcard']['icons'] = array(
    'default' => 'vcard.gif');


/**
 * RPM driver settings
 * Uncomment these lines to use this driver.
 */

/* Location of the rpm binary. */
// $mime_drivers['horde']['rpm']['location'] = '/usr/bin/rpm';
// $mime_drivers['horde']['rpm']['inline'] = false;
// $mime_drivers['horde']['rpm']['handles'] = array(
//     'application/x-rpm',
//     'x-extension/rpm');
// $mime_drivers['horde']['rpm']['icons'] = array(
//     'default' => 'rpm.gif');


/**
 * Debian package driver settings
 * Uncomment these lines to use this driver.
 */

/* Location of the dpkg binary. */
// $mime_drivers['horde']['deb']['location'] = '/usr/bin/dpkg';
// $mime_drivers['horde']['deb']['inline'] = false;
// $mime_drivers['horde']['deb']['handles'] = array(
//     'application/x-debian-package',
//     'x-extension/deb');
// $mime_drivers['horde']['deb']['icons'] = array(
//     'default' => 'deb.gif');


/**
 * Image settings
 */
$mime_drivers['horde']['images']['inline'] = false;
$mime_drivers['horde']['images']['icons'] = array(
    'default' => 'image.gif');
$mime_drivers['horde']['images']['handles'] = array(
    'image/bmp', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/pbm',
    'image/pgm', 'image/png', 'image/tiff', 'image/x-png');
