#!/usr/bin/env bats

load test_helper

@test "image build" {

  run build_image
  [ "$status" -eq 0 ]

}

@test "docker-compose up" {
    docker-compose -p arctest up --build -d

    [ "$?" -eq 0 ]
}

@test "tidy up" {
    docker-compose -p arctest stop
    yes | docker-compose -p arctest rm
    docker volume rm arctest_mysql

    [ "$?" -eq 0 ]
}
