#!/usr/bin/perl -w
#
# $Horde: horde/scripts/mime_mapping/convert.pl,v 1.4.2.3 2003/08/04 16:04:00 slusarz Exp $
#
# Copyright 2001 Anil Madhavapeddy <anil@recoil.org>
# Copyright 2002 Michael Slusarz <slusarz@bigworm.colorado.edu>
#
# See the enclosed file COPYING for license information (GPL).  If you
# did not receive this file, see http://www.fsf.org/copyleft/gpl.html.

use strict;
use Sys::Hostname;

# Files containing MIME extensions
my(@FILES) = qw(mime.types mime.types.horde);

# Variables used
local(*IN);
my(@out);
my(%exts);
my($maxlength) = 0;

# Map the mime extensions file(s) into the %ext hash
foreach (@FILES) {
    open(IN, $_) or warn("Could not open $_.");
    while (<IN>) {
        # Remove trailing whitespace
        chomp();

        # Skip comments
        next if (/^#/);

        # These are tab-delimited files. Skip the entry if there is no
        # extension information. 
        my(@fields) = split(/\s+/, $_, 2);
        if (exists($fields[1])) {
            foreach (split(/\s+/, $fields[1])) {
                $exts{$_} = $fields[0];
                if (length($_) > $maxlength) {
                    $maxlength = length($_);
                }
            }
        }
    }
    close(IN);
}

# Assemble/sort the extenstions into an output array
foreach (sort(keys(%exts))) {
    push(@out, "'" . $_ . "'" . " " x ($maxlength - length($_)) . " => '" . $exts{$_} . "'");
}

# Implode the output array into a PHP file.
print << 'HEADER';
<?php
/**
 * This file contains a mapping of common file extensions to
 * MIME types. It has been automatically generated from the
 * horde/scripts/mime_mapping directory.
 *
 * ALL changes should be made to horde/scripts/mime_mapping/mime.types.horde
 * or else they will be lost when this file is regenerated.
 *
 * Any unknown file extensions will automatically be mapped to
 * 'x-extension/<ext>' where <ext> is the unknown file extension.
 *
HEADER

# Add the generated information
print " * \$" . "Horde" . "\$\n";
print " *\n";
print " * Generated: " . (scalar localtime()) . " by " . getpwuid($<) .
      " on " . hostname() . "\n";
print " */\n";

print << 'HEADER';
$mime_extension_map = array(
HEADER
print join(",\n",@out);
print << 'FOOTER';

);
FOOTER

exit;
