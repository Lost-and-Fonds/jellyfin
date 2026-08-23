# Jellyfin plugin instructions

This repository owns Jellyfin protocol behavior and the plugin package payload.
It must not import Stashd core. Run `./tests/run.sh` for provider checks; core
owns PostgreSQL lifecycle and plugin-runtime integration checks.
