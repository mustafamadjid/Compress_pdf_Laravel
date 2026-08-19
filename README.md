# PDF Compressor

A simple web application for reducing PDF file sizes. The application is built with **Laravel**, **Livewire**, and **Ghostscript**.

This guide is intended for users who are not yet familiar with Laravel.

## 1. Install Required Software

Install the following software on your computer or server:

* PHP 8.2 or later
* Composer 2
* Node.js LTS and npm
* Git
* Ghostscript

After installation, open **PowerShell** on Windows or a **Terminal** on Linux/macOS and verify that the programs are available:

```bash
php --version
composer --version
node --version
npm --version
git --version
```

Check Ghostscript:

```bash
gs --version
```

If Windows does not recognize `gs`, locate the Ghostscript installation directory. The executable is usually named `gswin64c.exe`.

## 2. Clone the Repository

Replace the example URL below with the actual GitHub repository URL:

```bash
git clone https://github.com/USERNAME/REPOSITORY.git
cd REPOSITORY
```

For example, if the repository is named `pdf-compressor`:

```bash
git clone https://github.com/USERNAME/pdf-compressor.git
cd pdf-compressor
```

All commands below must be executed from the project directory, which is the folder containing the `artisan` file.

## 3. Install Dependencies

Run:

```bash
composer install
npm install
```

Composer installs the PHP dependencies, while npm installs the frontend dependencies.

## 4. Create the Environment File

### Windows PowerShell

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### Linux/macOS

```bash
cp .env.example .env
php artisan key:generate
```

The `key:generate` command creates the application encryption key.

Do not share the contents of your `.env` file.

## 5. Configure Ghostscript

Open the `.env` file and make sure the following value matches your system:

```dotenv
GHOSTSCRIPT_BINARY=gs
```

On Windows, if Ghostscript is not available in the system `PATH`, use the full path to the executable.

Example:

```dotenv
GHOSTSCRIPT_BINARY="C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe"
```

Adjust the version number and installation path according to your Ghostscript installation.

Other available PDF compressor settings:

```dotenv
PDF_COMPRESSOR_MAX_UPLOAD_MB=25
PDF_COMPRESSOR_RETENTION_MINUTES=60
PDF_COMPRESSOR_PROCESS_TIMEOUT=120
```

These values mean:

* Maximum upload size: 25 MB
* Temporary files are retained for 60 minutes
* Ghostscript processes are terminated after 120 seconds

## 6. Build the Frontend

Run:

```bash
npm run build
```

The build process should complete without errors.

This command generates production assets inside:

```text
public/build
```

## 7. Run the Application

Start the Laravel development server:

```bash
php artisan serve
```

Open your browser and visit:

```text
http://127.0.0.1:8000
```

Upload a PDF file, select a compression level, and click **Compress PDF**.

To stop the development server, press:

```text
Ctrl+C
```

in the terminal.

## 8. Run Tests

Run the automated test suite:

```bash
php artisan test
```

You can also verify code formatting and the frontend build:

```bash
./vendor/bin/pint --test
npm run build
```

On Windows PowerShell, run Pint using:

```powershell
.\vendor\bin\pint --test
```

The automated tests do not require Ghostscript because external processes are faked during service tests.

Ghostscript is still required when running the application normally.

## 9. Temporary File Cleanup

Uploaded files and compressed results are temporarily stored in:

```text
storage/app/temporary/uploads
storage/app/temporary/compressed
```

To manually remove expired temporary files, run:

```bash
php artisan pdf:cleanup
```

The application schedules cleanup every 30 minutes.

To make the Laravel scheduler run continuously on a Linux server, add the following cron entry:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Replace:

```text
/path/to/project
```

with the actual path to the application directory.

## 10. Troubleshooting

### `php is not recognized`

PHP is either not installed or its installation directory is not included in the system `PATH`.

Install PHP or use the terminal provided by tools such as Laragon or XAMPP, depending on your local environment.

### `composer is not recognized`

Install Composer, then close and reopen your terminal.

### `npm is not recognized`

Install the Node.js LTS version, then reopen your terminal.

### `gs is not recognized`

Install Ghostscript.

On Windows, set `GHOSTSCRIPT_BINARY` to the full path of `gswin64c.exe`.

Example:

```dotenv
GHOSTSCRIPT_BINARY="C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe"
```

### `Vite manifest not found`

Run:

```bash
npm install
npm run build
```

### `The PDF could not be compressed`

Check the following:

* Ghostscript is installed.
* `GHOSTSCRIPT_BINARY` points to the correct executable.
* The PDF file is not corrupted.
* The PDF file is not password-protected.
* The application has permission to write to the `storage/` directory.

## 11. Deploying to a Server

Before making the application publicly accessible:

* Set `APP_ENV=production`.
* Set `APP_DEBUG=false`.
* Set `APP_URL` to the actual application URL.
* Install Ghostscript on the server.
* Make sure `storage/` and `bootstrap/cache/` are writable.
* Run `npm run build`.
* Enable the Laravel scheduler.
* Configure PHP `upload_max_filesize` and `post_max_size` to be larger than the application's upload limit.
* Configure the Nginx `client_max_body_size` directive or the equivalent Apache request-body limit.
* Do not place `storage/app/temporary` inside the publicly accessible web directory.
* Use HTTPS.

The following components are not required for this MVP:

* Database
* User authentication
* Login system
* Redis
* Queue workers

## Security

The application includes several basic security measures:

* Uploaded files are validated by extension, MIME type, and file size.
* Internal filenames are generated using UUIDs.
* Ghostscript compression presets are defined by the application rather than supplied directly by users.
* Downloads use signed URLs with an expiration time.
* The client never sends filesystem paths.
* Technical error details are written to server logs, while the UI only displays generic error messages.
* Temporary files are cleaned up automatically.
