#!/usr/bin/env bash

cd /usr/local/my-setup

mongo admin < 'first-user.js'

echo "Added first user."