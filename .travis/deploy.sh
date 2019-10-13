#!/bin/sh
svn co -q "http://svn.wp-plugins.org/synchronize_wechat" /tmp/svn
rsync -rc --exclude-from="./distignore" ./ /tmp/svn/trunk --delete
cd /tmp/svn
svn add .
svn ci --username zhaofeng-shu33 --password $PASSWORD -m "$(git log -1 --pretty=%B)"