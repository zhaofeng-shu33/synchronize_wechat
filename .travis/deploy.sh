#!/bin/sh
svn co -q "http://plugins.svn.wordpress.org/synchronize-wechat" /tmp/svn
rsync -rc --exclude-from="./.distignore" ./ /tmp/svn/trunk --delete
MESSAGE="$(git log -1 --pretty=%B)"
cd /tmp/svn
svn add . --force
svn ci --no-auth-cache --non-interactive --username zhaofeng_shu33 --password $PASSWORD -m "$MESSAGE"