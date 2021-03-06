#!/bin/sh

obj_name="$1"
shift
git ls-files --stage \
| if grep -q "$obj_name"; then
    echo Found in staging area. Run git ls-files --stage to see.
fi

git log "$@" --pretty=format:'%T %h %s' \
| while read tree commit subject ; do
    if git ls-tree -r $tree | grep -q "$obj_name" ; then
        echo $commit "$subject"
    fi
done

#obj_name="$1"
#shift
#NFOUND=true
#git log "$@" --pretty=format:'%T %h %s' \
#| while read tree commit subject ; do
#    if git ls-tree -r $tree | grep -q "$obj_name" ; then
#        echo $commit "$subject"
#		NFOUND=false
#	fi
#done
#if $NFOUND; then
#	echo "Not Found"
#fi

#obj_name="$1"
#shift
#git log "$@" --pretty=format:'%T %h %s' \
#| while read tree commit subject ; do
#    if git ls-tree -r $tree | grep -q "$obj_name" ; then
#        echo $commit "$subject"
#    fi
#done
