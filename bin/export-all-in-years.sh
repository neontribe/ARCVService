#!/bin/bash

ARTISAN=$(dirname $0)/../artisan
$ARTISAN arc:mvl:export --chunk-size=999950 --to=30/03/2019
$ARTISAN arc:mvl:export --chunk-size=999950 --from=01/04/2019 --to=30/03/2020
$ARTISAN arc:mvl:export --chunk-size=999950 --from=01/04/2020 --to=30/03/2021
$ARTISAN arc:mvl:export --chunk-size=999950 --from=01/04/2021 --to=30/03/2022
$ARTISAN arc:mvl:export --chunk-size=999950 --from=01/04/2022 --to=30/03/2023
$ARTISAN arc:mvl:export --chunk-size=999950 --from=01/04/2023 --to=30/03/2024
