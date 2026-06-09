#!/bin/sh

echo "Installing the Archspace Portal"
sh portal/install.sh

echo "Installing Archspace..."

echo "Making Directories..."
mkdir -pv /etc/archspace
mkdir -pv /usr/src/archspace
mkdir -pv /var/archspace
mkdir -pv /var/log/archspace
mkdir -pv /var/www/localhost/htdocs

echo "Copying Files..."
cp -Rv src/* /usr/src/archspace
cp -Rv etc/* /etc/archspace
cp -Rv www/* /var/www/localhost/htdocs

# Install Module(s)
sh mod_as-1.3/install.sh

echo "Setting up init scripts"
ln -vs /usr/src/archspace/script /etc/archspace/script
sh /etc/archspace/set_lang en
cp -v init.d/Archspace /etc/init.d/Archspace
cp -v init.d/archspacemon /usr/sbin/archspacemon
cd /usr/src/archspace
sh /usr/src/archspace/set_platform linux
cd /usr/src/archspace/libs

echo "Compiling archspace server..."
make clean && make
cd /usr/src/archspace/apps/archspace
make clean && make
sh /etc/archspace/initialize_game
