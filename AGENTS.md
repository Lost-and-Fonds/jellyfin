# Jellyfin plugin instructions

This repository owns Jellyfin protocol behavior and the native package payload.
It must not import Stashd core. Run `./tests/run.sh` for provider checks; core
owns PostgreSQL lifecycle and native-runtime integration checks.
