#!/bin/bash
git rev-list --objects --all \
| git cat-file --batch-check='%(objecttype) %(objectname) %(objectsize) %(rest)' \
| awk '/^blob/ {print substr($0,6)}' \
| sort --numeric-sort --key=2 \
#| cut --complement --characters=8-40 \
#| numfmt --field=2 --to=iec-i --suffix=B --padding=7 --round=nearest

# | grep -vF "$(git ls-tree -r HEAD | awk '{print $3}')" \ (files not in HEAD)
# | awk '$2 >= 2^20' \ (files over 1MiB)


#for commitID given as result
#join -o "1.1 1.2 2.3" <(git rev-list --objects --all | sort) <(git verify-pack -v .git/objects/pack/*.idx | sort -k3 -n | tail -5 | sort) | sort -k3 -n
