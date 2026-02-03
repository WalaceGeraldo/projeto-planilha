#!/bin/bash
set -e

echo "Checking Apache MPM configuration..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

if [ ! -e /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    echo "Enabling mpm_prefork..."
    a2enmod mpm_prefork
fi

exec "$@"
