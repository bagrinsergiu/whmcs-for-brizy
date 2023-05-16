#!/bin/sh

WHMCS_NAME=$(cat ../whmcs-addon/modules/addons/brizy/brizy.php | grep -oP "'name' => '\K(.*)'" | cut -d "'" -f 1)
WHMCS_PLUGIN_VERSION=$(cat ../whmcs-addon/modules/addons/brizy/brizy.php | grep -oP "'version' => '\K(.*)'" | cut -d "'" -f 1)
RELEASE_NAME="${WHMCS_NAME}_${WHMCS_PLUGIN_VERSION}"

TARGET_DIR="${1:-/tmp/build}"
BUILD_NAME="${2:-$RELEASE_NAME}"
SCRIPT_DIR=$(dirname $(readlink -f "$0"))
MODULE_DIR="../whmcs-addon"
ADMIN_DIR="../brizy-admin"
PARENT_DIR="${SCRIPT_DIR}/.."


echo "CREATING RELEASE: ${BUILD_NAME} -> ${TARGET_DIR}'"

(cd $ADMIN_DIR && rm -rf node_modules && npm install --force)
npm run build:prod

mkdir -p ${TARGET_DIR}

# cleanup all files that are not needed
rm -rf ${TARGET_DIR}/.git
rm -rf ${TARGET_DIR}/.idea
# ...

cp -R ${MODULE_DIR}/. ${TARGET_DIR}/module_data/
(cd ${TARGET_DIR}/module_data/ && zip -r ${TARGET_DIR}/${BUILD_NAME}.zip .)

rm -rf ${TARGET_DIR}/module_data
