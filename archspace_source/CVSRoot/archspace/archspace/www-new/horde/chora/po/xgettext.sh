#!/bin/sh

perl extract.pl | xgettext --keyword=_ -C --no-location -
