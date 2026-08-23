<?php

declare(strict_types=1);

require_once '/sdk/bootstrap.php';
require_once __DIR__ . '/../src/JellyfinBroadcast.php';

(new Stashd\PluginSdk\Runtime\PluginServer(new Jellyfin\JellyfinBroadcast()))->run();
