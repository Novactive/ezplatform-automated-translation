<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Event;


final class RichTextEncodeEvent
{
    public function __construct(
        protected string $xmlValue
    ) {
    }

    public function getValue(): string
    {
        return $this->xmlValue;
    }

    public function setValue(string $value): void
    {
        $this->xmlValue = $value;
    }
}
