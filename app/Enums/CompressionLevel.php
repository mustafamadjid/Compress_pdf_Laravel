<?php

namespace App\Enums;

enum CompressionLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public static function default(): self
    {
        return self::Medium;
    }

    public static function fromInput(?string $value): self
    {
        return self::tryFrom(strtolower((string) $value)) ?? self::default();
    }

    public function ghostscriptPreset(): string
    {
        return match ($this) {
            self::Low => '/prepress',
            self::Medium => '/ebook',
            self::High => '/screen',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }
}
