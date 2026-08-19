<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupTemporaryPdfFiles extends Command
{
    protected $signature = 'pdf:cleanup';

    protected $description = 'Delete expired temporary PDF uploads and compressed files';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(config('pdf-compressor.retention_minutes'))->getTimestamp();
        $deleted = 0;

        foreach (['temporary/uploads', 'temporary/compressed'] as $directory) {
            foreach (Storage::disk('local')->files($directory) as $file) {
                try {
                    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
                        continue;
                    }

                    if (Storage::disk('local')->lastModified($file) < $cutoff && Storage::disk('local')->delete($file)) {
                        $deleted++;
                    }
                } catch (Throwable $throwable) {
                    Log::warning('Temporary PDF cleanup failed.', [
                        'file' => $file,
                        'exception' => $throwable->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Deleted {$deleted} expired temporary PDF file(s).");

        return self::SUCCESS;
    }
}
