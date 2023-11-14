<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle;

final class Events
{
    /**
     * @Event("\EzSystems\EzPlatformAutomatedTranslationBundle\Event\FieldEncodeEvent")
     */
    const POST_FIELD_ENCODE = 'ez_automated_translation.post_field_encode';

    /**
     * @Event("\EzSystems\EzPlatformAutomatedTranslationBundle\Event\FieldDecodeEvent")
     */
    const POST_FIELD_DECODE = 'ez_automated_translation.post_field_decode';

    /**
     * @Event("\EzSystems\EzPlatformAutomatedTranslationBundle\Event\RichTextEncodeEvent")
     */
    const PRE_RICHTEXT_ENCODE = 'ez_automated_translation.pre_richtext_encode';

    /**
     * @Event("\EzSystems\EzPlatformAutomatedTranslationBundle\Event\RichTextDecodeEvent")
     */
    const PRE_RICHTEXT_DECODE = 'ez_automated_translation.pre_richtext_decode';
}
