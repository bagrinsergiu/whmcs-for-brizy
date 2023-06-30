#!/bin/sh

SCRIPT_DIR=$(dirname $(readlink -f "$0"))
PARENT_DIR="${SCRIPT_DIR}/.."

RELEASE_NAME="${WHMCS_NAME}_${WHMCS_PLUGIN_VERSION}"
TARGET_DIR="${1:-/tmp/build}"
ARCHIVE_NAME="${2:-$RELEASE_NAME}"
MODULE_DIR="${PARENT_DIR}/whmcs-addon"

mkdir -p ${TARGET_DIR}

echo "CREATING RELEASE: ${ARCHIVE_NAME} -> ${TARGET_DIR}'"
cp -R ${MODULE_DIR}/. ${TARGET_DIR}/module_data/

( cd ${TARGET_DIR}/module_data/ && zip -r ${TARGET_DIR}/${ARCHIVE_NAME} . | ls -la ${TARGET_DIR} )