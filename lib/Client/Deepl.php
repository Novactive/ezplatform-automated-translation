<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Client;

use EzSystems\EzPlatformAutomatedTranslation\Exception\ClientNotConfiguredException;
use EzSystems\EzPlatformAutomatedTranslation\Exception\InvalidLanguageCodeException;
use GuzzleHttp\Client;

class Deepl implements ClientInterface
{
    /** @var string */
    private $authKey;

    /** @var string */
    private $baseUri;

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
        $this->baseUri = isset($configuration['baseUri']) ? $configuration['baseUri'] : 'https://api.deepl.com';
    }

    public function translate(string $payload, ?string $from, string $to): string
    {
        $parameters = [
            'auth_key' => $this->authKey,
            'target_lang' => $this->normalized($to),
            'tag_handling' => 'xml',
            'text' => $payload,
        ];

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
        $response = $http->post('/v2/translate', ['form_params' => $parameters]);
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

        $code = strtoupper(substr($languageCode, 0, 2));
        if (\in_array($code, self::LANGUAGE_CODES)) {
            return $code;
        }

        return null;
    }

    /**
     * List of available code https://www.deepl.com/api.html.
     */
    private const LANGUAGE_CODES = ['EN', 'DE', 'FR', 'ES', 'IT', 'NL', 'PL', 'JA'];
}
