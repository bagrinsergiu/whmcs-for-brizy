#!/bin/sh

TARGET_DIR="${1:-/tmp/build}"
BUILD_NAME="${2:-build.zip}"
SCRIPT_DIR=$(dirname $(readlink -f "$0"))
PARENT_DIR="${SCRIPT_DIR}/.."

mkdir -p ${TARGET_DIR}

cp -R ${PARENT_DIR} ${TARGET_DIR}

# cleanup all files that are not needed
rm -rf ${TARGET_DIR}/.git
rm -rf ${TARGET_DIR}/.idea
# ...

#
zip -r ${BUILD_NAME} ${TARGET_DIR}