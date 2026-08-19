<?php

namespace App\Livewire;

use App\Enums\CompressionLevel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class PdfCompressor extends Component
{
    use WithFileUploads;

    public $file;

    public string $compressionLevel = CompressionLevel::Medium->value;

    public ?string $originalFileName = null;

    public ?int $originalFileSize = null;

    public ?string $storedFilePath = null;

    public bool $isProcessing = false;

    public function updatedCompressionLevel(string $value): void
    {
        $this->compressionLevel = CompressionLevel::fromInput($value)->value;
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:'.(config('pdf-compressor.max_upload_mb') * 1024),
            ],
        ]);

        $this->originalFileName = $this->file->getClientOriginalName();
        $this->originalFileSize = $this->file->getSize();
        $this->storedFilePath = $this->file->storeAs(
            'temporary/uploads',
            Str::uuid().'.pdf',
            'local'
        );
    }

    public function resetFile(): void
    {
        if ($this->storedFilePath) {
            Storage::disk('local')->delete($this->storedFilePath);
        }

        $this->file = null;
        $this->originalFileName = null;
        $this->originalFileSize = null;
        $this->storedFilePath = null;
        $this->isProcessing = false;
        $this->resetErrorBag('file');
    }

    public function compress(): void
    {
        $this->isProcessing = true;
    }

    public function render()
    {
        return view('livewire.pdf-compressor');
    }
}
