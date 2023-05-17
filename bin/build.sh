#!/bin/sh

SCRIPT_DIR=$(dirname $(readlink -f "$0"))
PARENT_DIR="${SCRIPT_DIR}/.."

WHMCS_NAME=$(cat ${PARENT_DIR}/whmcs-addon/modules/addons/brizy/brizy.php | grep -oP "'name' => '\K(.*)'" | cut -d "'" -f 1)
WHMCS_PLUGIN_VERSION=$(cat ${PARENT_DIR}/whmcs-addon/modules/addons/brizy/brizy.php | grep -oP "'version' => '\K(.*)'" | cut -d "'" -f 1)

RELEASE_NAME="${WHMCS_NAME}_${WHMCS_PLUGIN_VERSION}"
TARGET_DIR="${1:-/tmp/build}"
BUILD_NAME="${2:-$RELEASE_NAME}"
MODULE_DIR="${PARENT_DIR}/whmcs-addon"



mkdir -p ${TARGET_DIR}

echo "CREATING RELEASE: ${BUILD_NAME} -> ${TARGET_DIR}'"
cp -R ${MODULE_DIR}/. ${TARGET_DIR}/module_data/

(cd ${TARGET_DIR}/module_data/ && zip -r ${TARGET_DIR}/${BUILD_NAME}.zip . && ls -la ${TARGET_DIR})

rm -rf ${TARGET_DIR}/module_data
