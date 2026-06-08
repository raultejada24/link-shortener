# URL Shortener

A basic URL shortener built with PHP and MySQL.

It creates seven-character short codes, redirects visitors to the original URL, and stores a click count.

## Requirements

- PHP 8.0 or newer with PDO MySQL.
- MySQL.

## Setup

Open MySQL and import the schema:

```sql
CREATE DATABASE url_shortener;
USE url_shortener;
SOURCE database-schema.sql;
```

Set the connection values in PowerShell when they differ from the defaults:

```powershell
$env:DB_HOST = "localhost"
$env:DB_PORT = "3306"
$env:DB_NAME = "url_shortener"
$env:DB_USER = "root"
$env:DB_PASSWORD = "your-password"
$env:APP_URL = "http://localhost:8000/api.php"
```

Start the application in the same terminal:

```powershell
php -S localhost:8000
```

## Test

Create a short URL:

```powershell
curl.exe -X POST http://localhost:8000/api.php `
  -H "Content-Type: application/json" `
  -d '{"url":"https://example.com"}'
```

Open the returned `short_url` in a browser to verify the redirect.
