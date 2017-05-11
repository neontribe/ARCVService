This directory contains unit tests for Models Commands, Listeners, Routes and Controllers.

A test should be created for every new function added to a Model, Command, Listener or Controller.

A test should be created for every new Route implemented.

There is some crossover with controllers and acceptance/ feature - so open to discussion.
Until we implement a service, testing the controller functions in Unit/Routes as they
all correspond to endpoints that return json data. If and when they do more stuff, combine
Dusk based browser page tests with unit tests on any processing methods.
