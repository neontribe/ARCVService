#!/bin/bash

# docker compose exec arc yarn does not run the .bashrc
# we could use a forced tty login, e.g. docker compose exec arc bash --login yarn
# we'll just wrap that up here

source ~/.bashrc
yarn $@