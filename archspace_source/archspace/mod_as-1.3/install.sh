#!/bin/sh
APACHE_MODULES_DIR=/usr/local/libexec/apache/
APACHE_CONFIG_DIR=/usr/local/etc/apache/
echo "Installing Archspace Apache Module"
echo
echo "Compiling..."
apxs -c *.c
rm *.o
echo
echo "Copying Files..."
mv -v mod_as.so ${APACHE_MODULES_DIR}mod_as.so
#cp -v mod_as.conf ${APACHE_CONFIG_DIR}mod_as.conf

echo "Archspace Apache Module Installed"
