<?php

declare(strict_types=1);

namespace App\Data;

use App\Casts\AuditStrategyCast;
use App\Casts\LanguageCast;
use App\Casts\SafeUrlCast;
use App\Enums\AuditStrategy;
use App\Enums\Language;
use App\ValueObjects\SafeUrl;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

final class ScanData extends Data
{
    public function __construct(
        #[WithCast(SafeUrlCast::class)]
        public SafeUrl $url,
        #[WithCast(LanguageCast::class)]
        public Language $lang,
        #[WithCast(AuditStrategyCast::class)]
        public AuditStrategy $strategy,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url'],
            'lang' => ['required', 'string', 'in:en,pt_BR,es'],
            'strategy' => ['required', 'string', 'in:mobile,desktop'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'url.required' => 'The URL field is required',
            'url.string' => 'The URL must be a string',
            'url.url' => 'The URL must be a valid URL',
            'lang.required' => 'The language field is required',
            'lang.in' => 'The language must be one of: en, pt_BR, es',
            'strategy.required' => 'The strategy field is required',
            'strategy.in' => 'The strategy must be either mobile or desktop',
        ];
    }
}
