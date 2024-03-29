<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

class TranslatableConfig
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Application Locales
            |--------------------------------------------------------------------------
            |
            | Contains an array with the applications available locales.
            |
            */
            'locales'            => ['en', 'nl'],

            /*
            |--------------------------------------------------------------------------
            | Use fallback
            |--------------------------------------------------------------------------
            |
            | Determine if fallback locales are returned by default or not. To add
            | more flexibility and configure this option per "translatable"
            | instance, this value will be overridden by the property
            | $useTranslationFallback when defined
            */
            'use_fallback'       => true,

            /*
            |--------------------------------------------------------------------------
            | Fallback Locale
            |--------------------------------------------------------------------------
            |
            | A fallback locale is the locale being used to return a translation
            | when the requested translation is not existing. To disable it
            | set it to false.
            |
            */
            'fallback_locale'    => 'nl',

            /*
            |--------------------------------------------------------------------------
            | Translation Suffix
            |--------------------------------------------------------------------------
            |
            | Defines the default 'Translation' class suffix. For example, if
            | you want to use CountryTrans instead of CountryTranslation
            | application, set this to 'Trans'.
            |
            */
            'translation_suffix' => 'Translation',

            /*
            |--------------------------------------------------------------------------
            | Locale key
            |--------------------------------------------------------------------------
            |
            | Defines the 'locale' field name, which is used by the
            | translation model.
            |
            */
            'locale_key'         => 'locale',

            /*
            |--------------------------------------------------------------------------
            | Make translated attributes always fillable
            |--------------------------------------------------------------------------
            |
            | If true, translatable automatically sets
            | translated attributes as fillable.
            |
            | WARNING!
            | Set this to true only if you understand the security risks.
            |
            */
            'always_fillable'    => false,
        ];
    }
}
