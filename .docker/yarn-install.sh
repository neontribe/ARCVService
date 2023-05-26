#!/bin/bash

cd /opt/project
source /root/.nvm/nvm.sh
nvm install lts/gallium
npm i --global yarn
yarn
yarn prod
