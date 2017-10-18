#!/bin/bash

DIR="$(dirname "${BASH_SOURCE[0]}")"

find "$DIR" -type f -name '*.php' -exec php "{}" \;