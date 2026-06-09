#!/usr/bin/perl
#
# $Horde: chora/po/extract.pl,v 1.1 2001/08/03 20:36:39 avsm Exp $
#
# Perl script to extract strings from all the files and print
# to stdout for use with xgettext.

use File::Find;
use Cwd;
use strict;

my($ext) = '(\.php$|\.inc$|\.dist$)';
my(%strings);

find(\&extract, cwd . '/..');

print join("\n", sort keys %strings), "\n";

sub extract
{
  my($file) = $File::Find::name;

  if ($file =~ /$ext/) {
    open F, $file;
    while (<F>) {
      while (s/_\("(.*?)"\)//) {
        $strings{"_(\"$1\")"}++;
      }
    }
    close F;
  }
}
