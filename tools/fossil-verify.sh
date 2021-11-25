#!/bin/bash

REPO="$1"

if [ ! -f "$1/manifest" ]
then
	echo "Missing manifest"
	echo "Usage: $0 FOSSIL_REPOSITORY_PATH"
	exit 1
fi

gpg --verify "$1/manifest" 2> /dev/null

if [ $? != 0 ]
then
	echo "Manifest signature failed to verify"
	exit 2
fi

TMPFILE=$(mktemp)

while IFS= read -r LINE
do
	if [ "${LINE:0:2}" != "F " ]
	then
		echo "$LINE" >> $TMPFILE
		continue
	fi

	# Split string by spaces
	PARTS=($LINE)

	FILE_ENCODED="${PARTS[1]}"
	FILE="${PARTS[1]//\\s/ }"
	HASH="${PARTS[2]}"

	if [ "${#HASH}" = 40 ]
	then
		NEW_HASH=$(sha1sum "$1/$FILE" | awk '{print $1}')
	else
		NEW_HASH=$(openssl dgst -sha3-256 -binary "$1/$FILE" | xxd -p -c 100)
	fi

	if [ "$HASH" != "$NEW_HASH" ]
	then
		echo "Local file has changed"
		echo "$FILE"
		echo "Manifest hash:   $HASH"
		echo "Local file hash: $NEW_HASH"
		exit 2
	fi

	PARTS[2]="$HASH"

	# join parts in a new string
	NEW_LINE="$(printf " %s" "${PARTS[@]}")"
	NEW_LINE="${NEW_LINE:1}"

	echo "$NEW_LINE" >> $TMPFILE
done < "$1/manifest"

gpg --verify $TMPFILE 2>/dev/null

if [ $? != 0]
then
	echo "Something has changed between manifest and check?!"
	diff "$1/manifest" $TMPFILE
	rm -f $TMPFILE
	exit 2
fi

rm -f $TMPFILE
exit 0