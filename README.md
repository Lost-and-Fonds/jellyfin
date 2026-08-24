# Stashd Jellyfin plugin

Jellyfin Broadcast plugin for Stashd. It publishes eligible Vault video
assets into a rebuildable Jellyfin-compatible layout and can test, discover, and
refresh a configured Jellyfin server.

Configure a Jellyfin server connection and API credential in Stashd. The plugin
uses the granted connection and credential; it does not access core records or
the Vault directly.

Production installation uses Stashd's OCI installer (`stashd:plugin-install
ghcr.io/lost-and-fonds/jellyfin:<version>`). Composer is for local plugin
development only. Run `composer test`; application lifecycle coverage belongs
to the core integration suite.

## Release artifact

The OCI artifact contains this provider and its production dependencies; this provider declares no helpers.
