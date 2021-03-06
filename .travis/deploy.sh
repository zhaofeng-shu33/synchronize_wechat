#!/bin/sh
set -e -x
svn co -q "http://plugins.svn.wordpress.org/synchronize-wechat" /tmp/svn
rsync -rc --exclude-from="./.distignore" ./ /tmp/svn/trunk --delete
if [ -z "$TRAVIS_TAG" ]; then
   if [ "$TRAVIS_BRANCH" = "master" -a "$TRAVIS_EVENT_TYPE" = "push" ]; then
     MESSAGE="$(git log -1 --pretty=%B)"
   else
     exit 0
   fi
elif [ "$TRAVIS_EVENT_TYPE" = "push" ]; then
    MESSAGE="bump to version $TRAVIS_TAG"
    VERSION=$(echo $TRAVIS_TAG | sed "s/v//")
    mkdir /tmp/svn/tags/$VERSION
    rsync -r /tmp/svn/trunk /tmp/svn/tags/$VERSION
fi
cd /tmp/svn
svn add . --force
svn ci --no-auth-cache --non-interactive --username zhaofengshu33 --password $PASSWORD -m "$MESSAGE"

