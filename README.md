# PDF Compressor

Single-page Laravel and Livewire application for compressing one PDF with Ghostscript. Files stay in private temporary storage and expire automatically.

## Requirements

- PHP 8.2 or newer with extensions required by Laravel
- Composer 2
- Node.js and npm
- Ghostscript
- Writable `storage/` and `bootstrap/cache/`

Ghostscript executable is commonly `gs` on Linux and `gswin64c.exe` on Windows. Confirm installation:

```bash
gs --version
```

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan serve
```

On Windows PowerShell, replace `cp .env.example .env` with:

```powershell
Copy-Item .env.example .env
```

No database is required for application workflow. Default session and cache drivers use filesystem storage.

## Configuration

Set these values in `.env`:

```dotenv
PDF_COMPRESSOR_MAX_UPLOAD_MB=25
PDF_COMPRESSOR_RETENTION_MINUTES=60
PDF_COMPRESSOR_PROCESS_TIMEOUT=120
GHOSTSCRIPT_BINARY=gs
```

`GHOSTSCRIPT_BINARY` may contain an absolute executable path. User input never controls this value or Ghostscript arguments.

## Running

Start development server:

```bash
php artisan serve
```

For frontend development:

```bash
npm run dev
```

## Tests

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

Automated tests fake process execution where practical, so test suite does not require Ghostscript. Manually verify real compression with text-heavy, image-heavy, scanned, optimized, corrupted, and password-protected PDFs before production release.

## Temporary Files

Uploads and compressed files use private local storage:

```text
storage/app/temporary/uploads
storage/app/temporary/compressed
```

Run cleanup manually:

```bash
php artisan pdf:cleanup
```

Cleanup is scheduled every 30 minutes. Production server must invoke Laravel scheduler every minute:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

## Production Checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, and correct `APP_URL`.
- Install Ghostscript and set `GHOSTSCRIPT_BINARY` to executable name or absolute path.
- Make `storage/` and `bootstrap/cache/` writable by web process.
- Run `npm run build` during deployment.
- Configure scheduler cron shown above.
- Set PHP `upload_max_filesize` and `post_max_size` above `PDF_COMPRESSOR_MAX_UPLOAD_MB`.
- Set Nginx `client_max_body_size` or Apache request body limit above application upload limit.
- Keep `storage/app/temporary` outside public document root.
- Ensure web process can create and delete temporary files.
- Tune `PDF_COMPRESSOR_PROCESS_TIMEOUT` for server capacity and expected PDFs.
- Serve application over HTTPS so signed download URLs remain private in transit.

## Security Model

- Upload validates extension, MIME type, and configured size.
- Internal filenames use UUIDs instead of original names.
- Ghostscript receives structured process arguments and fixed application presets.
- Download uses expiring signed URLs and UUID route constraints.
- Technical failures are logged server-side; UI receives generic errors.
- Scheduled cleanup removes expired temporary PDFs.
