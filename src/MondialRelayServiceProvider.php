<?php

namespace SmartDato\MondialRelay;

use SmartDato\MondialRelay\V1\Client;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MondialRelayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mondial-relay-sdk')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Client::class, fn () => new Client(
            enseigne: config('mondial-relay-sdk.v1.enseigne') ?? '',
            privateKey: config('mondial-relay-sdk.v1.private_key') ?? '',
            url: config('mondial-relay-sdk.v1.url') ?? Client::DEFAULT_URL,
        ));

        $this->app->singleton(MondialRelay::class, fn () => new MondialRelay(
            client: $this->app->make(Client::class),
            defaultLanguage: config('mondial-relay-sdk.default_language', 'FR'),
        ));
    }
}
