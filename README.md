# Stashd Jellyfin plugin

Native Jellyfin Broadcast plugin for Stashd. It publishes eligible Vault video
assets into a rebuildable Jellyfin-compatible layout and can test, discover, and
refresh a configured Jellyfin server.

Configure a Jellyfin server connection and API credential in Stashd. The plugin
uses the granted connection and credential; it does not access core records or
the Vault directly.

Install as `stashd/jellyfin`. Run the provider contract check with
`./tests/run.sh`; application lifecycle coverage belongs to the core
integration suite.
