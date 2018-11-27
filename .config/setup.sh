#!/usr/bin/env bash
export VAGRANT_CWD=chassis

# Double-ensure that all submodules are checked out
printf "\n-----------------------------------\n"
printf "Ensure Git submodules are installed"
printf "\n-----------------------------------\n"
git submodule update --init --recursive

# Install Chassis.
printf "\n----------------------------------------\n"
printf "Setting up Chassis config and installing"
printf "\n----------------------------------------\n"
git clone --progress --recursive git@github.com:Chassis/Chassis.git chassis
cp .config/config.local.yaml chassis/
cp .config/local-config.php chassis/
cp wp-config-local-sample.php wp-config-local.php
vagrant up

# Install Composer inside VM.
printf "\n--------------------------------\n"
printf "Installing Composer dependencies"
printf "\n--------------------------------\n"
composer install

# Install Javascript libraries.
printf "\n----------------------------------\n"
printf "Installing Javascript dependencies"
printf "\n----------------------------------\n"
vagrant ssh -c "curl -sL https://deb.nodesource.com/setup_10.x -o node_setup.sh && sudo bash node_setup.sh"
vagrant ssh -c "sudo apt-get install -y nodejs"
vagrant ssh -c "cd /chassis && npm install"

printf "\n------------\n"
printf "Opening site"
printf "\n------------\n"
# Define a function to run xdg-open (Linux) or open (OSX), whichever is available.
openurl() {
	if hash xdg-open 2>/dev/null; then
		xdg-open "$@"
	else
		open "$@"
	fi
}
openurl http://planet4.local/wp-login.php

echo "Installation complete"
