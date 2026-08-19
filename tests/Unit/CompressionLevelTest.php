<?php

namespace Tests\Unit;

use App\Enums\CompressionLevel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompressionLevelTest extends TestCase
{
    #[Test]
    public function valid_compression_levels_are_available(): void
    {
        $levels = array_map(
            static fn (CompressionLevel $level): string => $level->value,
            CompressionLevel::cases(),
        );

        $this->assertSame(['low', 'medium', 'high'], $levels);
    }

    #[Test]
    public function medium_is_default_level(): void
    {
        $this->assertSame(CompressionLevel::Medium, CompressionLevel::default());
        $this->assertSame(CompressionLevel::Medium, CompressionLevel::fromInput(null));
        $this->assertSame(CompressionLevel::Medium, CompressionLevel::fromInput('invalid'));
    }

    #[Test]
    public function user_input_maps_only_to_known_levels(): void
    {
        $this->assertSame(CompressionLevel::Low, CompressionLevel::fromInput('low'));
        $this->assertSame(CompressionLevel::High, CompressionLevel::fromInput('HIGH'));
        $this->assertSame('/ebook', CompressionLevel::fromInput('ebook')->ghostscriptPreset());
    }

    #[Test]
    public function levels_map_to_internal_ghostscript_presets(): void
    {
        $this->assertSame('/prepress', CompressionLevel::Low->ghostscriptPreset());
        $this->assertSame('/ebook', CompressionLevel::Medium->ghostscriptPreset());
        $this->assertSame('/screen', CompressionLevel::High->ghostscriptPreset());
    }
}
