This directory contains unit tests for Models Commands, Listeners and Routes.

A test should be created for every new function added to a Model, Command or Listener.

A test should be created for every new Route implemented.

There is some crossover with controllers and acceptance/ feature.

Until we implement a service, testing the controller functions in Unit/Routes as they
all correspond to endpoints that return json data.

If and when they do more stuff, combine Dusk based browser page tests with unit tests on any processing methods.
