#!/bin/sh
obj_name="$1"
shift
NFOUND=true
git log "$@" --pretty=format:'%T %h %s' \
| while read tree commit subject ; do
    if git ls-tree -r $tree | grep -q "$obj_name" ; then
        echo $commit "$subject"
		NFOUND=false
	fi
done
if $NFOUND; then
	echo "Not Found"
fi