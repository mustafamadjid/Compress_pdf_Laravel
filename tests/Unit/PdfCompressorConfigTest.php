<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfCompressorConfigTest extends TestCase
{
    #[Test]
    public function pdf_compressor_configuration_loads_defaults(): void
    {
        $this->assertSame(25, config('pdf-compressor.max_upload_mb'));
        $this->assertSame(60, config('pdf-compressor.retention_minutes'));
        $this->assertSame(120, config('pdf-compressor.process_timeout'));
        $this->assertSame('gs', config('pdf-compressor.ghostscript_binary'));
    }
}
