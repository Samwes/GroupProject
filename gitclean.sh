#!/usr/bin/env bash

git clean -f -i -d
git remote prune origin
git reflog expire --expire=now --expire-unreachable=now --all
git gc --prune=now --aggressive