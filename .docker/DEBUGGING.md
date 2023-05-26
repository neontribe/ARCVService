# DEBUGGING

## Build the Image

Run the ARC Server with `docker compose up --build`

## Install the Chrome XDebug helper

https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc

## Tell PHPStorm to use the docker

1. Use Alt-Ctl-S to open settings
2. Search for PHP
3. Check that Language version is 8.1
4. On the CLI Interpreter click ...
5. On the next window Click +
6. Choose from docker, Vagrant, WSL....
7. Click the radio button for docker
8. Choose the Image name neontribe/arc:dev from the drop down
9. Click OK

## Tweak the debug settings

1. In Settings, navigate to PHP -> Debug
2. Find external connections and un-check "Break at first line in PHP scripts"
3. Click OK

# Test it

1. Start the container (docker compose up) and load the webpage in chrome
2. Choose the Debug option from the XDebug help extension - the bug goes green
3. Open public/index.php and add a break point on line 36 (`$app = require_once __DIR__.'/../bootstrap/app.php';`)
4. Click the telephone icon in the run toolbar at the top of the page
5. Reload the Arc Service page
6. Accept the pop up dialog, it should show a mapping to <PROJECTHOME>/server.php (Only happens the first time ti runs)
7. Watch the system break at line 36
8. Click the red stop button in the run toolbar to stop the debugger (twice, once for the page once for the debug toolbar)
9. Click the phone icon to stop listening

Congratulations you're s debugger.
