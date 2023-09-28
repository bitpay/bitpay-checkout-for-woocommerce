#!/bin/bash

if [[ -z "$SVN_USERNAME" ]]; then
  echo "Set the SVN_USERNAME secret"
  exit 1
fi

if [[ -z "$SVN_PASSWORD" ]]; then
  echo "Set the SVN_PASSWORD secret"
  exit 1
fi

GITHUB_REF=${GITHUB_REF#refs/tags/} # refs/tags/v1.0.0 -> v1.0.0
VERSION="${GITHUB_REF#v}" # v1.0.0 -> 1.0.0

rm -rf .git
rm -rf vendor

SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="${HOME}/svn-${SLUG}"

svn checkout --quiet "$SVN_URL" "$SVN_DIR" > /dev/null

if [[ -d "tags/$VERSION" ]]; then
  echo "ℹ︎ Version $VERSION of plugin $SLUG was already published";
  exit
fi

git config --global --add safe.directory "$GITHUB_WORKSPACE"
git config --global user.email ""
git config --global user.name "GitHub Actions"

mkdir $SVN_DIR/tags/$VERSION -p

echo "Copy project to SVN"
cp -R $GITHUB_WORKSPACE/* $SVN_DIR/tags/$VERSION

cd $SVN_DIR
echo "Add version to tags"
svn add tags/$VERSION

echo "Commit"
svn commit -m "Tagging version $VERSION" --no-auth-cache --non-interactive --force-interactive --username "$SVN_USERNAME" --password "$SVN_PASSWORD"















#
#
#
#
#
#ASSETS_DIR=".wordpress-org"
#
#SVN_URL="https://plugins.svn.wordpress.org/${SVN_SLUG}/"
#SVN_DIR="${HOME}/svn-${SVN_SLUG}"
#
#svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"
#cd "$SVN_DIR"
#
#svn update --set-depth infinity assets
#svn update --set-depth infinity trunk
#svn update --set-depth immediates tags
#
#
#
#
#generate_zip() {
#  if $INPUT_GENERATE_ZIP; then
#    echo "Generating zip file..."
#
#    # use a symbolic link so the directory in the zip matches the slug
#    ln -s "${SVN_DIR}/trunk" "${SVN_DIR}/${SLUG}"
#    zip -r "${GITHUB_WORKSPACE}/${SLUG}.zip" "$SLUG"
#    unlink "${SVN_DIR}/${SLUG}"
#
#    echo "zip-path=${GITHUB_WORKSPACE}/${SLUG}.zip" >> "${GITHUB_OUTPUT}"
#    echo "✓ Zip file generated!"
#  fi
#}
#
## Bail early if the plugin version is already published.
#if [[ -d "tags/$VERSION" ]]; then
#	echo "ℹ︎ Version $VERSION of plugin $SLUG was already published";
#
#	generate_zip
#
#	exit
#fi
#
#if [[ "$BUILD_DIR" = false ]]; then
#	echo "➤ Copying files..."
#
#		cd "$GITHUB_WORKSPACE"
#
#		# "Export" a cleaned copy to a temp directory
#		TMP_DIR="${HOME}/archivetmp"
#		mkdir "$TMP_DIR"
#
#		# Workaround for: detected dubious ownership in repository at '/github/workspace' issue.
#		# see: https://github.com/10up/action-wordpress-plugin-deploy/issues/116
#		# Mark github workspace as safe directory.
#		git config --global --add safe.directory "$GITHUB_WORKSPACE"
#		git config --global user.email "10upbot+github@10up.com"
#		git config --global user.name "10upbot on GitHub"
#
#		# Ensure git archive will pick up any changed files in the directory try.
#		test $(git ls-files --deleted) && git rm $(git ls-files --deleted)
#		if [ -n "$(git status --porcelain --untracked-files=all)" ]; then
#			git add .
#			git commit -m "Include build step changes"
#		fi
#
#
#		# This will exclude everything in the .gitattributes file with the export-ignore flag
#		git archive HEAD | tar x --directory="$TMP_DIR"
#
#		cd "$SVN_DIR"
#
#		# Copy from clean copy to /trunk, excluding dotorg assets
#		# The --delete flag will delete anything in destination that no longer exists in source
#		rsync -rc "$TMP_DIR/" trunk/ --delete --delete-excluded
#	fi
#else
#	echo "ℹ︎ Copying files from build directory..."
#	rsync -rc "$BUILD_DIR/" trunk/ --delete --delete-excluded
#fi
#
## Copy dotorg assets to /assets
#if [[ -d "$GITHUB_WORKSPACE/$ASSETS_DIR/" ]]; then
#	rsync -rc "$GITHUB_WORKSPACE/$ASSETS_DIR/" assets/ --delete
#else
#	echo "ℹ︎ No assets directory found; skipping asset copy"
#fi
#
## Add everything and commit to SVN
## The force flag ensures we recurse into subdirectories even if they are already added
## Suppress stdout in favor of svn status later for readability
#echo "➤ Preparing files..."
#svn add . --force > /dev/null
#
## SVN delete all deleted files
## Also suppress stdout here
#svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null
#
## Copy tag locally to make this a single commit
#echo "➤ Copying tag..."
#svn cp "trunk" "tags/$VERSION"
#
## Fix screenshots getting force downloaded when clicking them
## https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
#if test -d "$SVN_DIR/assets" && test -n "$(find "$SVN_DIR/assets" -maxdepth 1 -name "*.png" -print -quit)"; then
#    svn propset svn:mime-type "image/png" "$SVN_DIR/assets/"*.png || true
#fi
#if test -d "$SVN_DIR/assets" && test -n "$(find "$SVN_DIR/assets" -maxdepth 1 -name "*.jpg" -print -quit)"; then
#    svn propset svn:mime-type "image/jpeg" "$SVN_DIR/assets/"*.jpg || true
#fi
#if test -d "$SVN_DIR/assets" && test -n "$(find "$SVN_DIR/assets" -maxdepth 1 -name "*.gif" -print -quit)"; then
#    svn propset svn:mime-type "image/gif" "$SVN_DIR/assets/"*.gif || true
#fi
#if test -d "$SVN_DIR/assets" && test -n "$(find "$SVN_DIR/assets" -maxdepth 1 -name "*.svg" -print -quit)"; then
#    svn propset svn:mime-type "image/svg+xml" "$SVN_DIR/assets/"*.svg || true
#fi
#
##Resolves => SVN commit failed: Directory out of date
#svn update
#
#svn status
#
#if $INPUT_DRY_RUN; then
#  echo "➤ Dry run: Files not committed."
#else
#  echo "➤ Committing files..."
#  svn commit -m "Update to version $VERSION from GitHub" --no-auth-cache --non-interactive  --username "$SVN_USERNAME" --password "$SVN_PASSWORD"
#fi
#
#generate_zip
#
#echo "✓ Plugin deployed!"
