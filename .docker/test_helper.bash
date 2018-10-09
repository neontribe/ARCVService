
setup() {
  IMAGE_NAME="$NAME:$VERSION"
}


build_image() {
  #disable outputs
  docker build -t $IMAGE_NAME $BATS_TEST_DIRNAME/../.docker &> /dev/null
}
