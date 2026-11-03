<?php

namespace App\Integrations;

use App\Enums\ConsentCategory;
use App\Enums\IntegrationProvider;

/**
 * What each provider needs. `config` is validated against these rules, and `idKey` names the
 * setting used to de-duplicate the same tag across scopes.
 */
final class Providers
{
    /**
     * @return array{rules: array<string, list<string>>, idKey: string, consent: ConsentCategory}
     */
    public static function definition(IntegrationProvider $provider): array
    {
        return match ($provider) {
            IntegrationProvider::Ga4 => [
                'rules' => ['measurement_id' => ['required', 'string', 'regex:/^G-[A-Z0-9]{4,20}$/']],
                'idKey' => 'measurement_id',
                'consent' => ConsentCategory::Analytics,
            ],
            IntegrationProvider::GoogleAds => [
                'rules' => [
                    'conversion_id' => ['required', 'string', 'regex:/^AW-[0-9]{6,15}$/'],
                    'conversion_label' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{1,64}$/'],
                ],
                'idKey' => 'conversion_id',
                'consent' => ConsentCategory::Ads,
            ],
            IntegrationProvider::Gtm => [
                'rules' => ['container_id' => ['required', 'string', 'regex:/^GTM-[A-Z0-9]{4,12}$/']],
                'idKey' => 'container_id',
                'consent' => ConsentCategory::Analytics,
            ],
            IntegrationProvider::MetaPixel => [
                'rules' => ['pixel_id' => ['required', 'string', 'regex:/^[0-9]{6,20}$/']],
                'idKey' => 'pixel_id',
                'consent' => ConsentCategory::Ads,
            ],
            IntegrationProvider::Clarity => [
                'rules' => ['project_id' => ['required', 'string', 'regex:/^[a-z0-9]{6,20}$/']],
                'idKey' => 'project_id',
                'consent' => ConsentCategory::Analytics,
            ],
            IntegrationProvider::Plausible => [
                'rules' => [
                    'domain' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)([a-z0-9-]+\.)+[a-z]{2,}$/i'],
                    'script_url' => ['nullable', 'url:https', 'max:255'],
                ],
                'idKey' => 'domain',
                'consent' => ConsentCategory::Analytics,
            ],
            IntegrationProvider::Custom => [
                'rules' => [
                    'name' => ['required', 'string', 'max:80'],
                    'html' => ['required', 'string', 'max:20000'],
                ],
                'idKey' => 'name',
                'consent' => ConsentCategory::Functional,
            ],
        };
    }

    /**
     * Prefixed rules for the request (`config.measurement_id`…).
     *
     * @return array<string, list<string>>
     */
    public static function rules(IntegrationProvider $provider): array
    {
        $rules = [];

        foreach (self::definition($provider)['rules'] as $key => $keyRules) {
            $rules["config.{$key}"] = $keyRules;
        }

        return $rules;
    }

    /**
     * The value that identifies one tag of this provider, e.g. "ga4:G-ABC123".
     *
     * @param  array<string, mixed>  $config
     */
    public static function identity(IntegrationProvider $provider, array $config): string
    {
        return $provider->value.':'.mb_strtolower((string) ($config[self::definition($provider)['idKey']] ?? ''));
    }
}
