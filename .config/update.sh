#!/usr/bin/env bash
export VAGRANT_CWD=chassis-dflcis

printf "\n------------------------\n"
printf "Updating local resources"
printf "\n------------------------\n"

# Double-ensure that all submodules are checked out
printf "\n-----------------------------------\n"
printf "Ensure Git submodules are installed"
printf "\n-----------------------------------\n"
git pull
git submodule sync
git submodule update --init --recursive

# Run composer install
printf "\n-----------------------------\n"
printf "Updating Composer libraries"
printf "\n-----------------------------\n"
composer install

# Install Javascript libraries..
printf "\n-----------------------------\n"
printf "Updating Javascript tooling"
printf "\n-----------------------------\n"
vagrant ssh -c "cd /chassis && npm install"

echo "Resource updating complete ✅"
