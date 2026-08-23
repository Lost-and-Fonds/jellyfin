#!/bin/sh
set -eu
php "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)/run.php"
