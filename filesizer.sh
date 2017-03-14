#!/bin/bash
# git rev-list --objects master |git cat-file --batch-check='%(objectsize) %(objecttype) %(objectname) %(rest)'

git rev-list HEAD |                     # list commits
  xargs -n1 git ls-tree -rl |             # expand their trees
  sed -e 's/[^ ]* [^ ]* \(.*\)\t.*/\1/' | # keep only sha-1 and size
  sort -u |                               # eliminate duplicates
  awk '{ sum += $2 } END { print sum / 10000000 }'   # add up the sizes in bytes