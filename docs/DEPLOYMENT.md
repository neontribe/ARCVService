## Physical server set up

We have two droplet virtual machines at [Digital Ocean](https://cloud.digitalocean.com).

They exist at in LON1 for easier GDPR compliance in regard to domestic data storage.

They are configured with the following Digital Ocean hardware parameters:

### Staging

Basic / 4 GB / 2 "regular" Intel vCPUs / 50Gb SSD / 4TB transfer @ $24.00 per month

### Live

Basic / 8 GB / 4 "Premium" Intel vCPUs / 160Gb SSD / 5TB transfer @ $56.00 per month

## OS Platform

Both virtual machines have [Rocky linux](https://rockylinux.org/) (v9.x) and have identical baseline configurations, regardless of underlying hardware.

### Updates

The machines have their packages updated monthly using the OS update facility (DNF) and so a fresh install of the current 9.x should be fine.

### Command Line access
Access is via keyed SSH with restricted IP, or in extremis, the console functionality at Digital Ocean.

### Networking

The Machines each have virtual NICs with an internal IP4 address and an internet facing static IP4 address. These addresses are supplied by Digital Ocean.

Firewalling is provided via the OS firewall.

## Application Software stacks

The staging server was hand rolled to provide the LAMP stack below. Live was a VM clone of staging. These have been live since March 2023.

### Apache Web Server

```
[neontribe@rocky9-arc-staging ~]$ apachectl -v
Server version: Apache/2.4.62 (Rocky Linux)
Server built:   Aug  3 2024 00:00:00
```

Apache binds multiple virtual hosts:

Staging: (138.68.180.157)
- voucher-staging.alexadrarose.org.uk -> static Market App files
- voucher-store-staging.alexandrarose.org.uk -> Laravel 
- voucher-admin-staging.alexandrarose.org.uk -> Laravel

Live: (138.68.140.141)
- voucher.alexandrarose.org.uk -> static Market App files
- voucher-store.alexandrarose.org.uk -> Laravel
- voucher-admin.alexandrarose.org.uk -> Laravel

The application can disambiguate requests for the web route based on the request parameters.

### DNS

Entries for the hostnames are in the `alexandrarose.org.uk` subdomain, managed by ARC's DNS and Email support staff.

### PHP 

```
[neontribe@rocky9-arc-staging ~]$  php-fpm -v
PHP 8.2.27 (cli) (built: Dec 17 2024 11:39:23) (NTS gcc x86_64)
Copyright (c) The PHP Group
Zend Engine v4.2.27, Copyright (c) Zend Technologies
    with Zend OPcache v8.2.27, Copyright (c), by Zend Technologies
```

PHP has the following modules enabled:

bcmath,bz2,calendar,Core,ctype,curl,date,dom,exif,fileinfo,filter,ftp,gd,gettext,hash,iconv,json,libxml,mbstring,mysql,mysqli,mysqlnd,openssl,pcntl,pcre,PDO,pdo_mysql,pdo_sqlite,Phar,posix,random,readline,Reflection,session,shmop,SimpleXML,sockets,sodium,SPL,sqlite3,standard,sysvmsg,sysvsem,sysvshm,tokenizer,xml,xmlreader,xmlwriter,xsl,Zend OPcache,zip,zlib

### MySQL

```
[neontribe@rocky9-arc-staging ~]$ mysqld -V
mysql  Ver 8.0.36 for Linux on x86_64 (Source distribution)
```

### Mail

The service does not require an email service. It passes any mail it needs to send using the Laravel mandrill plugin to ARCs MailChimp account for forwarding.

### Application Components

The ARC service comprises 

* A statically built and served [VueJS](https://v2.vuejs.org/) 2.x PWA Trader application:

    - that installs on the user's device where it can
    - manages the redemption and reconciliation of trader's voucher transactions 
    - uses the Laravel instance as REST API to read/write data.

* A Laravel application that serves:

    - a portal for administration of the entities (vouchers / centres/ areas / markets and various user accounts) in the service
    - a portal for the management of voucher distribution at children's centres
    - a REST API to serve the user authentication needs and fulfil requests from the trader application
    - persist shared data into a mysql server.

The raw mysql files for this instance are:

Staging
```
[root@rocky9-arc-staging mysql]# du -sh .
208M    .
```
Live
```
[root@Arc-live-04-2023 mysql]# du -sh .
5.1G     .
```

## Service Interaction

Defined in [server-components.puml](images/server-components.puml)

![Transition table](images/server-components.png "Service Interactions")

## SupervisorD and CronD managed processes

The system uses [Supervisord](http://supervisord.org/) to run an instance of the Laravel application as a database backed queue worker with responsibilities like processing long-running tasks like fetching voucher histories.

It is important to remember to reload this after a code change, as it will maintain the loaded classes in memory and fail to pickup changes.

There is a cron job (`/etc/cron.d/laravel`) that is run very frequently (every minute) to execute Laravel's internal [task scheduling system](../app/Console/Kernel.php).
