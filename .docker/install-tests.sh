#!/bin/bash

sudo apt-get update && sudo apt-get install -y libxpm4 libxrender1 libgtk2.0-0 libnss3 libgconf-2-4
sudo apt-get install -y chromium-browser
sudo apt-get install -y xvfb gtk2-engines-pixbuf
sudo apt-get -y install xfonts-cyrillic xfonts-100dpi xfonts-75dpi xfonts-base xfonts-scalable
sudo apt-get -y install imagemagick x11-apps
Xvfb -ac :0 -screen 0 1280x1024x16 &
chmod a+x ./vendor/laravel/dusk/bin/chromedriver-linux
./vendor/laravel/dusk/bin/chromedriver-linux --port=8888 &
php artisan serve &
