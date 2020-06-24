## 5.5 to 5.6

Files changed:

    git diff 6ddb3f37..2121018 --name-only

### Update composer

    git diff 6ddb3f37..21210183 composer.json

### Laravel link generator has changed

The >> symbol in links was changed to > somewhere in the laravel core, the tests needed updateing.

    git diff 6ddb3f37..21210183 resources/views/service/includes/sidebar.blade.php tests/Feature/Store/SearchPageTest.php

### Laravel error handler
 
Laravel error handler added a field to error messages

    git diff 6ddb3f37..21210183 tests/Unit/Passport/RoutesTest.php tests/Unit/Routes/ApiRoutesTest.php

## 5.6 to 5.7

Files changed (includes changes from 1.10/release):

    git diff 8fc9ee37..9394ba10 --name-only

### Updated composer

    git diff 8fc9ee37..9394ba10 composer.json
    
### SQLite dropping forgein keys

SLQLite cannot drop foreign keys, all migrations that drop FKs need to altered

    git diff 8fc9ee37..9394ba10 database/migrations/*
    
### Console output needs to be mocked 

Added ```public $mockConsoleOutput = false``` to fix Artisan changes in testing

    git diff 8fc9ee37..9394ba10 tests/Unit/Passport/RoutesTest.php tests/Unit/Routes/ApiRoutesTest.php

## 5.7 to 5.8

Files Changed (includes changes from 1.10/release):

    git diff ee7b777a..2633eca1 --name-only

### Rolled up composer.json
   
    git diff ee7b777a..2633eca1 composer.json
    
### Added return type to setUp prototype for tests
    
Change ```protected function setUp()``` to ```protected function setUp(): void```

    git diff ee7b777a..2633eca1 tests
    
### SQL definitions need to be specific
    
Changed ```$this->history()->get(null)->last();``` to ```$this->history()->get("*")->last();```

    git diff ee7b777a..2633eca1 app/Traits/Statable.php

## 5.8 to 6 - NOT YET COMPLETED

We still have three fails around passport and session.  I'm going to wait for Charlie to help fix them.

Files Changed:

    git diff 86f4e7bc..7a4e92bf --name-only

### Rolled up composer.json

Removed Optimus\ApiConsumer forked Chalcedonyt\Specification

    git diff b954aaea..86f4e7bc composer.json

### Route parameters must be explicitly named
    
e.g.

    URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ])
    
must now be named to match the route spec
    
    URL::route('store.registration.voucher-manager', [ 'registration' => $this->registration ])

&nbsp;

    git diff 86f4e7bc..94956ea73

### New required logging config

    git diff 3b76d548aa..5de4eaa2c
    
### Swapped or for ??

    git diff 5de4eaa2c..962290d0

### Added data dir to git keep

    git diff 962290d0..fbe45941ff

### Replace Excel lib with native php

    git diff fbe45941ff..cbe5aa6e2

### Reduced accuracy of phpunit to any 7.x

assertJsonStructure no longer works
    
There is a circular decency issue caused by spinen/laravel-mail-assertions which needs a pinned version of phpunit but that breaks the laravel json package.

    git diff 4a68c0e7b..7f4ff2bd3




