<div>
    <div>
        <h1 class="text-4xl font-semibold tracking-tight text-slate-50 sm:text-5xl">PDF Compressor</h1>
        <p class="mt-3 max-w-xl text-base leading-relaxed text-slate-400">
            Reduce your PDF file size quickly. Upload a PDF, choose a compression level, and download the result.
        </p>
    </div>

    <form wire:submit.prevent="compress" class="mt-8 space-y-6">
        <div>
            <label
                for="pdf"
                class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-700 bg-slate-800/40 px-6 py-10 text-center transition-colors hover:border-cyan-400/60 hover:bg-slate-800/60 focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-400/30"
            >
                <span class="mb-3 inline-flex size-12 items-center justify-center rounded-full bg-cyan-400/10 text-cyan-300">
                    <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
                        <path d="M12 16V4m0 0 4 4m-4-4L8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="text-sm font-medium text-slate-200">Drop your PDF here or browse</span>
                <span class="mt-1 text-xs text-slate-500">PDF files up to {{ config('pdf-compressor.max_upload_mb') }} MB</span>
                <input id="pdf" type="file" accept=".pdf,application/pdf" wire:model="file" class="sr-only">
            </label>

            @error('file')
                <p class="mt-3 rounded-lg bg-red-500/10 px-4 py-2.5 text-sm text-red-300">{{ $message }}</p>
            @enderror
        </div>

        @if ($originalFileName)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-700/70 bg-slate-800/50 px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-400/10 text-red-300">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="M14 3v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-200">{{ $originalFileName }}</p>
                        <p class="text-xs text-slate-500">{{ number_format($originalFileSize / 1048576, 2) }} MB</p>
                    </div>
                </div>
                <button type="button" wire:click="resetFile" class="shrink-0 text-xs font-medium text-slate-400 transition-colors hover:text-slate-200">
                    Remove
                </button>
            </div>
        @endif

        <fieldset>
            <legend class="text-sm font-medium text-slate-300">Compression level</legend>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                @foreach (\App\Enums\CompressionLevel::cases() as $level)
                    <label
                        class="relative flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors {{ $compressionLevel === $level->value ? 'border-cyan-400/60 bg-cyan-400/10' : 'border-slate-700/70 bg-slate-800/40 hover:border-slate-600' }}"
                    >
                        <input
                            type="radio"
                            name="compressionLevel"
                            value="{{ $level->value }}"
                            wire:model="compressionLevel"
                            class="sr-only"
                        >
                        <span class="mt-0.5 inline-flex size-4 items-center justify-center rounded-full border-2 {{ $compressionLevel === $level->value ? 'border-cyan-400' : 'border-slate-600' }}">
                            @if ($compressionLevel === $level->value)
                                <span class="size-2 rounded-full bg-cyan-400"></span>
                            @endif
                        </span>
                        <span>
                            <span class="block text-sm font-medium text-slate-200">{{ $level->label() }}</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">
                                {{ match ($level) {
                                    \App\Enums\CompressionLevel::Low => 'Highest visual quality',
                                    \App\Enums\CompressionLevel::Medium => 'Balanced quality and size',
                                    \App\Enums\CompressionLevel::High => 'Smallest file size',
                                } }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="compress"
                {{ $file || $isProcessing ? '' : 'disabled' }}
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-500 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition-transform duration-150 active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading wire:target="compress" class="inline-block size-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                <span wire:loading wire:target="compress">Compressing...</span>
                <span wire:loading.remove wire:target="compress">Compress PDF</span>
            </button>
        </div>

        @if ($compressionError)
            <p class="rounded-xl bg-red-500/10 px-4 py-3 text-sm text-red-300" role="alert">{{ $compressionError }}</p>
        @endif

        @if ($result && isset($result['original_size']))
            <section class="rounded-2xl border border-emerald-400/25 bg-emerald-400/5 p-5" aria-live="polite">
                <p class="text-sm font-semibold text-emerald-300">Compression Complete</p>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Original Size</dt><dd class="mt-1 font-medium text-slate-100">{{ number_format($result['original_size'] / 1048576, 2) }} MB</dd></div>
                    <div><dt class="text-slate-500">Compressed Size</dt><dd class="mt-1 font-medium text-slate-100">{{ number_format($result['compressed_size'] / 1048576, 2) }} MB</dd></div>
                    <div><dt class="text-slate-500">Reduction</dt><dd class="mt-1 font-medium text-slate-100">{{ number_format($result['reduction_percentage'], 2) }}%</dd></div>
                    <div><dt class="text-slate-500">Compression Level</dt><dd class="mt-1 font-medium text-slate-100">{{ \App\Enums\CompressionLevel::fromInput($result['compression_level'])->label() }}</dd></div>
                </dl>
                @if ($result['compressed_size'] >= $result['original_size'])
                    <p class="mt-4 text-sm leading-relaxed text-amber-200">This PDF is already well optimized and could not be reduced significantly.</p>
                @endif
                <a href="{{ $result['download_url'] }}" class="mt-5 inline-flex items-center justify-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition-transform duration-150 active:scale-[0.97]">
                    Download PDF
                </a>
                <button type="button" wire:click="resetCompression" class="mt-5 ml-2 inline-flex items-center justify-center rounded-xl border border-slate-600 px-5 py-2.5 text-sm font-semibold text-slate-200 transition-transform duration-150 active:scale-[0.97]">
                    Compress Another PDF
                </button>
            </section>
        @endif
    </form>
</div>
