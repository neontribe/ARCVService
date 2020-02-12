# How to set up Laravel Homestead on MacOS and Linux

## Pre-requisites
- Before launching your Homestead environment, make sure you've installed VirtualBox 5.2 and Vagrant
- You can use VMWare, Parellels or Hyper-V instead of VirtualBox but this guide refers to VirtualBox unless otherwise specified
- Read the steps below before you begin the setup process to get an idea of what you're getting into

## Installation
1. Add the `laravel/homestead` box to your Vagrant installation using this command in your terminal:
**`vagrant box add laravel/homestead`**
2. When prompted, select `virtualbox` as provider
3. Install Homestead by cloning the repository into a Homestead folder within your "home" directory using this command
**`git clone https://github.com/laravel/homestead.git ~/Homestead && cd ~/Homestead`**
¬ creates a `Homestead` folder within your home directory and changes your directory to that
4. Check out a tagged version of Homestead since the master branch may not always be stable. For example: `git checkout v9.0.7`

## Local Configuration
5. From the Homestead directory, run the **`bash init.sh`** command to create the `Homestead.yaml` configuration file
6. Edit the `Homestead.yaml` file and make sure to configure the Folders and Sites sections to be of this form:

```
	folders:
	    - map: /var/www/html/ARCV (linux) OR
	    - map: /Users/username/code-directory-title/ARCV (Mac)
	      to: /home/vagrant/code
	sites:
	    - map: arcv-service.test
	      to: /home/vagrant/code/ARCVService/public
	      php: "add php version number"
	    - map: arcv-store.test
	      to: /home/vagrant/code/ARCVService/public
	      php: "add php version number"
```
7. In your `/etc/hosts` add `192.168.10.10 arcv-service.test arcv-store.test`
8. Once you have edited the `Homestead.yaml` to your liking, run the **`vagrant up`** command from your Homestead directory

***

## VM Configuration
9. If successful, enter the vm with **`vagrant ssh`**
10. You should be in `home/vagrant`  and that should contain a `code` directory that is mapped to your development directory (where you keep your repos) from local (see step 6, folders -> map)
 11. copy the `.env.example` file contents to `.env` and edit the `.env`  to local settings

Do the steps below:
-  `composer install`
- `php artisan key:generate` - key is automatically added to the `.env` field
- `php artisan migrate --seed`
- `php artisan passport:install` to create keys and client
- `chmod 600 ./storage/*.key` to set permissions correctly
-  Add the "password grant client" id and secret to your `.env`
- [install nvm](https://github.com/nvm-sh/nvm#installing-and-updating) and then exit the vm (`exit` or `ctrl+D`) and re-enter it (`vagrant ssh` command) and navigate back to your ARCVService directory
- `nvm install lts/carbon`
- `nvm use`
- Install npm packages for webpack (JS and Sass) builds: `yarn install`
- Run `yarn dev` to make sure packages Store shares with Service have been included
- Compile Service from Sass with `yarn prod`

***
In your browser of choice, go to `https://arcv-service.test` or `https://arcv-store.test` - try with `http`, too. Check the browser's developer tools console and network tabs for errors.

## Resources
1. [Laravel Homestead official documentation](https://laravel.com/docs/5.5/homestead)
2. [ARC Service Readme file](https://github.com/neontribe/ARCVService/blob/1.8/release/README.md)