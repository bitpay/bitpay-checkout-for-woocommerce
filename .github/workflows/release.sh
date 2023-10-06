#!/bin/bash

if [[ -z "$SVN_USERNAME" ]]; then
  echo "Set the SVN_USERNAME secret"
  exit 1
fi

if [[ -z "$SVN_PASSWORD" ]]; then
  echo "Set the SVN_PASSWORD secret"
  exit 1
fi

VERSION=${GITHUB_REF#refs/tags/} # refs/tags/1.0.0 -> v1.0.0

SVN_URL="https://plugins.svn.wordpress.org/${SLUG}/"
SVN_DIR="${HOME}/svn-${SLUG}"

svn checkout --quiet "$SVN_URL" "$SVN_DIR" > /dev/null

if [[ -d "tags/$VERSION" ]]; then
  echo "ℹ︎ Version $VERSION of plugin $SLUG was already published";
  exit
fi

mkdir $SVN_DIR/tags/$VERSION -p

echo "Copy project to SVN"
cp -R $GITHUB_WORKSPACE/* $SVN_DIR/tags/$VERSION

cd $SVN_DIR
echo "Add version to tags"
svn add tags/$VERSION

echo "Commit"
svn commit -m "Tagging version $VERSION" --no-auth-cache --non-interactive --username "$SVN_USERNAME" --password "$SVN_PASSWORD"