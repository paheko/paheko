#!/bin/bash
set -xe

dir=$(dirname $(realpath "${0}"))
tests_dir=$(realpath ${dir}/..)

mkdir -p doc
REDOCLY_TELEMETRY=off npx @redocly/cli build-docs --config ${tests_dir}/api/doc-config.yml --output ${tests_dir}/doc/api.html --disableGoogleFont
curl -so doc/redoc.standalone.js $(grep -Po 'https://cdn.redocly.com/.+/redoc.standalone.js' ${tests_dir}/doc/api.html)
sed -r 's!https://cdn.redocly.com/.+/redoc.standalone.js!./redoc.standalone.js!' -i ${tests_dir}/doc/api.html
