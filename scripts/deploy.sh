#!/usr/bin/env bash

if [[ -z "${1}" ]]; then
	echo "WordPress.org password not set" 1>&2
	exit 1
fi

WP_ORG_PASSWORD=${1}

if [[ -z "${GITHUB_REF}" ]]; then
	echo "Script is only to be run by github action" 1>&2
	exit 1
fi

CURRENT_TAG=${GITHUB_REF##*/}

if [[ -z "${CURRENT_TAG}" ]]; then
	echo "Build must be a tag" 1>&2
	exit 0
fi

WP_ORG_USERNAME="GM_Alex"
PLUGIN="user-access-manager"
PROJECT_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
PLUGIN_BUILDS_PATH="${PROJECT_ROOT}/builds"

PLUGIN_MAIN_FILE_CONTENT=$(cat ${PROJECT_ROOT}/${PLUGIN}.php)
VERSION_REGEX='.*Version:[ ]*([0-9]+\.[0-9]+\.[0-9]+-?[A-Za-z]*).*'

if [[ ${PLUGIN_MAIN_FILE_CONTENT} =~ ${VERSION_REGEX} ]]; then
    VERSION=${BASH_REMATCH[1]}
else
    echo "Unable to identify current plugin version"
    exit 1
fi

PLUGIN_README_FILE_CONTENT=$(cat ${PROJECT_ROOT}/readme.txt)
STABLE_VERSION_REGEX='.*Stable tag:[ ]*([0-9]+\.[0-9]+\.[0-9]+-?[A-Za-z]*).*'

if [[ ${PLUGIN_README_FILE_CONTENT} =~ ${STABLE_VERSION_REGEX} ]]; then
    STABLE_VERSION=${BASH_REMATCH[1]}
else
    echo "Unable to identify stable plugin version"
    exit 1
fi

if [[ ${VERSION} != ${CURRENT_TAG} ]]; then
    echo "Tag ${CURRENT_TAG} version must match plugin version ${VERSION}."
    exit 1
fi

# Check if the tag exists for the version we are building
TAG=$(svn ls "https://plugins.svn.wordpress.org/${PLUGIN}/tags/${VERSION}")
ERROR=$?
if [[ ${ERROR} == 0 ]]; then
    # Tag exists, don't deploy
    echo "Tag already exists for version ${VERSION}, aborting deployment"
    exit 1
fi

cd "${PLUGIN_BUILDS_PATH}"

# Clean up any previous svn dir
rm -fR svn

# Checkout the SVN repo
svn co -q "http://svn.wp-plugins.org/${PLUGIN}" svn

# Move out the trunk directory to a temp location
mv svn/trunk ./svn-trunk
# Create trunk directory
mkdir svn/trunk
# Copy our new version of the plugin into trunk
rsync -r -p ${PLUGIN}/* svn/trunk

# Copy all the .svn folders from the checked out copy of trunk to the new trunk.
cd svn/trunk/
TARGET=$(pwd)
cd ../../svn-trunk/

# Find all .svn dirs in sub dirs
SVN_DIRS=`find . -type d -iname .svn`

for SVN_DIR in ${SVN_DIRS}; do
    SOURCE_DIR=${SVN_DIR/.}
    TARGET_DIR=${TARGET}${SOURCE_DIR/.svn}
    TARGET_SVN_DIR=${TARGET}${SVN_DIR/.}
    if [ -d "${TARGET_DIR}" ]; then
        # Copy the .svn directory to trunk dir
        cp -r ${SVN_DIR} ${TARGET_SVN_DIR}
    fi
done

# Back to builds dir
cd ../

# Remove checked out dir
rm -fR svn-trunk

# Add new version tag
mkdir svn/tags/${VERSION}
rsync -r -p ${PLUGIN}/* svn/tags/${VERSION}

# Add new files to SVN
svn stat svn | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# Remove deleted files from SVN
svn stat svn | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@
svn stat svn

# Commit to SVN
svn ci --no-auth-cache --username ${WP_ORG_USERNAME} --password ${WP_ORG_PASSWORD} svn -m "Deploy version ${VERSION}"

# Remove SVN temp dir
rm -fR svn