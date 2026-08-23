# Dependency decisions

Jellyfin uses the SDK HTTP/JSON capability and PHP's JSON extension. No Jellyfin SDK is used: the plugin only needs a small, controlled set of endpoints and the additional client would obscure the broker boundary.
