# Jellyfin plugin instructions

This repository owns Jellyfin protocol behavior and the plugin package payload.
It must not import Stashd core. Core owns PostgreSQL lifecycle and plugin-runtime
integration. Use PHP 8.5, PER-CS3, strict PSR-4, and code-as-paragraphs vertical
spacing. Use `composer lint` and `composer analyse` for source checks.
