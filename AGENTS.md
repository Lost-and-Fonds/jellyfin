# Jellyfin plugin instructions

This repository owns Jellyfin protocol behavior and the plugin package payload.
It must not import Stashd core. Core owns PostgreSQL lifecycle and plugin-runtime
integration checks. Use PHP 8.5,
PER-CS3, strict PSR-4, and code-as-paragraphs vertical spacing. Run `composer
lint`, `composer test`, and `composer test:static`.
