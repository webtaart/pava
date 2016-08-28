#!/bin/sh
set -x
svn cp http://plugins.svn.wordpress.org/celery-wp/trunk http://plugins.svn.wordpress.org/celery-wp/tags/$1 -m "Release $1"