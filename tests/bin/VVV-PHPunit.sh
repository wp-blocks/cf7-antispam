#!/bin/bash

#Get the args with or default
SKIP_DB_CREATION=${1:-false}
DB_USER=${2:-root}
DB_PASS=${3:-root}
DB_NAME=${4:-cf7a_tests}
DB_HOST=${5:-127.0.0.1}
WP_VERSION=${6:-latest}

# Whoareyou
current_username=$(whoami)

# Get the directory of the current script
script_dir=$(dirname "$(realpath "$0")")

# Substitute the local path with the vagrant path
srv_path=$(echo "$script_dir" | sed "s|/home/$current_username/vvv-local|/srv|g")

# Call vagrant ssh with the new path
vagrant ssh -t -c "chmod +x $srv_path/install-wp-tests.sh && $srv_path/install-wp-tests.sh $DB_NAME $DB_USER $DB_PASS $DB_HOST $WP_VERSION $SKIP_DB_CREATION"

