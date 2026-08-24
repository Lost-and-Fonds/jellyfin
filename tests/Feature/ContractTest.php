<?php

declare(strict_types=1);

use Jellyfin\JellyfinBroadcast;
use Stashd\PluginSdk as Sdk;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/JellyfinBroadcast.php';

it('preserves the Jellyfin provider contract', function (): void {
    function jellyfinAssert(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    final class JellyfinFixtureHttp implements Sdk\HttpClient
    {
        /** @var list<array{method: string, url: string, credential: ?string}> */
        public array $requests = [];

        public function request(string $method, string $url, array $headers = [], ?string $body = null, ?string $credential = null): Sdk\HttpResponse
        {
            jellyfinAssert($credential === 'jellyfin-api-token', 'credential grant was not requested');
            $this->requests[] = compact('method', 'url', 'credential');

            return match ($url) {
                'https://jellyfin.test/System/Info/Public' => new Sdk\HttpResponse(200, [], json_encode(['ServerName' => 'Fixture Jellyfin', 'Version' => '10.9'], JSON_THROW_ON_ERROR)),
                'https://jellyfin.test/Library/MediaFolders' => new Sdk\HttpResponse(200, [], json_encode(['Items' => [['Id' => 'tv', 'Name' => 'TV']]], JSON_THROW_ON_ERROR)),
                'https://jellyfin.test/Library/Refresh' => new Sdk\HttpResponse(204),
                default => new Sdk\HttpResponse(404, [], 'not found'),
            };
        }
    }

    $plugin = new JellyfinBroadcast();
    $settings = [
        new Sdk\Setting('server_url', Sdk\OptionValue::text('https://jellyfin.test')),
        new Sdk\Setting('credential_name', Sdk\OptionValue::text('jellyfin-api-token')),
    ];
    $request = new Sdk\PublishRequest('broadcast-1', $settings, [
        new Sdk\Source('source-1', [new Sdk\Setting('season', Sdk\OptionValue::number(3))]),
    ], [
        new Sdk\Item('item-1', 'A/Title', [new Sdk\ItemResource('asset-1', 'video')], 'source-1'),
    ]);
    $publication = $plugin->publish($request);
    jellyfinAssert(($publication->files[0]->relativePath ?? null) === 'Season 03/S03E01 - A_Title.mp4', 'publication layout changed');

    $http = new JellyfinFixtureHttp();
    $context = new Sdk\PluginContext(http: $http);
    $operation = $plugin->operation(new Sdk\OperationRequest('discover-libraries', $settings), $context);
    jellyfinAssert(($operation->choices[0]->value ?? null) === 'tv', 'library discovery failed');
    $plugin->finalize(new Sdk\FinalizationRequest($request, $publication), $context);
    jellyfinAssert(count($http->requests) === 2, 'expected discovery and refresh requests');

    expect(true)->toBeTrue();
});
