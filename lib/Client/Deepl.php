<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Client;

use EzSystems\EzPlatformAutomatedTranslation\Exception\ClientNotConfiguredException;
use GuzzleHttp\Client;

class Deepl implements ClientInterface
{
    /** @var string */
    private string $authKey;

    /** @var string */
    private string $baseUri;

    /** @var array */
    private array $nonSplittingTags;

    /** @var array */
    private array $supportedLanguagesMapping = [];

    public function getServiceAlias(): string
    {
        return 'deepl';
    }

    public function getServiceFullName(): string
    {
        return 'Deepl';
    }

    public function setConfiguration(array $configuration): void
    {
        if (!isset($configuration['authKey'])) {
            throw new ClientNotConfiguredException('authKey is required');
        }
        $this->authKey = $configuration['authKey'];
        $this->baseUri = $configuration['baseUri'] ?? 'https://api.deepl.com';
        if (isset($configuration['nonSplittingTags'])) {
            $this->nonSplittingTags = array_filter(explode(',', $configuration['nonSplittingTags']));
        }
        if (isset($configuration['supported_languages_mapping'])) {
            $this->supportedLanguagesMapping = array_unique((array) $configuration['supported_languages_mapping']);
        }

    }

    public function translate(string $payload, ?string $from, string $to): string
    {
        $parameters = [
            'target_lang' => $this->normalized($to),
            'tag_handling' => 'xml',
            'text' => [$payload]
        ];

        if (!empty($this->nonSplittingTags)){
            $parameters['non_splitting_tags'] = $this->nonSplittingTags;
        }

        if (null !== $from) {
            $parameters += [
                'source_lang' => $this->normalized($from),
            ];
        }

        $http = new Client(
            [
                'base_uri' => $this->baseUri,
                'timeout' => 5.0,
            ]
        );
        $response = $http->post('/v2/translate', [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $this->authKey
            ],
            'json' => $parameters
        ]);
        // May use the native json method from guzzle
        $json = json_decode($response->getBody()->getContents());

        return $json->translations[0]->text;
    }

    public function supportsLanguage(string $languageCode): bool
    {
        return \in_array($this->normalized($languageCode), self::LANGUAGE_CODES);
    }

    private function normalized(string $languageCode): string|null
    {
        if (\in_array($languageCode, self::LANGUAGE_CODES)) {
            return $languageCode;
        }
        if (isset($this->supportedLanguagesMapping[$languageCode])) {
            return $this->supportedLanguagesMapping[$languageCode];
        }

        $code = strtoupper(substr($languageCode, 0, 2));
        if (\in_array($code, self::LANGUAGE_CODES)) {
            return $code;
        }

        return null;
    }

    /**
     * List of available code https://www.deepl.com/docs-api/translate-text
     */
    private const LANGUAGE_CODES = ['BG', 'CS','DA', 'DE', 'EL', 'EN','EN-GB','EN-US', 'ES', 'ET',
        'FI','FR', 'HU', 'ID', 'IT', 'JA', 'KO', 'LT', 'LV', 'NB', 'NL', 'PL', 'PT', 'PT-BR', 'PT-PT', 'RO',
         'RU', 'SK', 'SL', 'SV', 'TR', 'UK', 'ZH'
    ];
}
