#!/bin/bash
set -e

# Disable all MPM modules to prevent conflicts
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2dismod mpm_prefork 2>/dev/null || true

# Enable only mpm_prefork (which PHP requires)
a2enmod mpm_prefork

# Start Apache in the foreground
exec apache2-foreground