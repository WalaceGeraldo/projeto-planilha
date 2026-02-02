#!/bin/bash
set -e

# Fix Apache MPM conflict at runtime
# This ensures that even if something was messed up during build, we fix it before starting.
echo "Checking Apache MPM configuration..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

# Ensure prefork is enabled (required for PHP)
if [ ! -e /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    echo "Enabling mpm_prefork..."
    a2enmod mpm_prefork
fi

# Execute the main container command (apache2-foreground)
exec "$@"
