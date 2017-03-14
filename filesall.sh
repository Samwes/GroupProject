#!/bin/bash
git rev-list --objects --all | sort -k 2 > allfileshas.txt
#    git rev-list --objects --all | sort -k 2 | cut -f 2 -d\  | uniq > allfileshas.txt
git gc && git verify-pack -v .git/objects/pack/pack-*.idx | egrep "^\w+ blob\W+[0-9]+ [0-9]+ [0-9]+$" | sort -k 3 -n -r > bigobjects.txt

for SHA in `cut -f 1 -d\  < bigobjects.txt`; do
echo $(grep $SHA bigobjects.txt) $(grep $SHA allfileshas.txt) | awk '{print $1,$3,$7}' >> bigtosmall.txt
done;

#git filter-branch --prune-empty --index-filter 'git rm -rf --cached --ignore-unmatch MY-BIG-DIRECTORY-OR-FILE' --tag-name-filter cat -- --all
#git clone --no-hardlinks file:///Users/yourUser/your/full/repo/path repo-clone-name
#du -s -h *(./)     # add the -h flag to see the output in human readable size formats, just like ls -lah vs ls -l