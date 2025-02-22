# Laravel Meta Generator

A Laravel package designed to effortlessly add and manage metadata for your Eloquent models without modifying their primary tables. Leverage a flexible key-value system with automatic type detection, casting, and useful artisan commands for seamless setup and maintenance.

---

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Artisan Commands](#artisan-commands)
  - [make:metadata](#makemetadata)
  - [metadata:clean-orphaned](#metadataclean-orphaned)
- [Configuration](#configuration)
- [License](#license)

---

## Installation

There are two ways to include this package in your Laravel project:

### 1. Via Packagist

If the package is published on Packagist, simply run:

```bash
composer require august/laravel-meta-table
```

Laravel will automatically detect the service provider via package discovery. If necessary, add the service provider manually in your `config/app.php`:

```php
'providers' => [
    // Other Service Providers

    August\MetaGenerator\ServiceProvider::class,
],
```

### 2. Using a Local Repository

If the package is not yet published on Packagist, you can add it as a local repository. In your project's `composer.json`, add the following under the `repositories` section:

```json
"repositories": [
    {
        "type": "path",
        "url": "../laravel-meta-table"
    }
],
```

Then run:

```bash
composer require august/laravel-meta-table
```

This tells Composer to look for the package in the specified local path.

---

## Usage

This package allows you to attach metadata to your Eloquent models without modifying your original database tables. After installation, you can use the artisan commands provided or integrate the functionality directly into your models.

### Attaching Metadata to a Model

1. **Generate Metadata Files:**

   Use the artisan command to generate the metadata system for an existing model. For example, to generate metadata for a `Book` model:

   ```bash
   php artisan make:metadata --model=Book
   ```

   This command does the following:
   - **Creates a Meta Model File:** A new file (e.g., `BookMeta.php`) will be created in your application's `Models` directory. This model extends the package's base `MetaModel` class.
   - **Updates the Original Model:** The package automatically updates your original model file (e.g., `Book.php`) to include the `HasMetadata` trait.
   - **Creates a Migration:** A new migration will be generated to create the corresponding metadata table (e.g., `book_meta`).

2. **Managing Metadata:**

   Once setup, your model gains several helpful methods via the `HasMetadata` trait:
   
   - **Retrieving Metadata:**
     ```php
     $value = $book->getMeta('author');
     ```
   
   - **Setting Metadata:**
     ```php
     $book->setMeta('publisher', 'Acme Publishing');
     ```

   - **Bulk Updating Metadata:**
     ```php
     $book->setManyMeta([
         'isbn' => '1234567890',
         'pages' => 350,
         'published_at' => '2025-02-22'
     ]);
     ```

   - **Syncing Metadata:**
     ```php
     $book->syncMeta([
         'genre' => 'Fiction',
         'language' => 'English'
     ]);
     ```

---

## Artisan Commands

### `make:metadata`

This command assists in setting up the metadata infrastructure for a specific model.

**Usage:**

```bash
php artisan make:metadata --model=ModelName
```

**What It Does:**

- Generates a new meta model file in `app/Models` (e.g., `ModelNameMeta.php`).
- Updates the corresponding original model to include the `HasMetadata` trait.
- Creates a migration file to build the metadata table (e.g., `model_name_meta`).

### `metadata:clean-orphaned`

This command cleans up orphaned metadata records in case the parent model has been deleted.

**Usage:**

```bash
php artisan metadata:clean-orphaned --model=ModelName
```

**What It Does:**

- Checks the metadata table for records whose associated parent record no longer exists.
- Prompts for confirmation before deleting any orphaned metadata records.
- Provides a summary of deleted records.

---

## Configuration

This package requires no additional configuration. After installation, simply run the artisan commands as needed. However, ensure that your application's models are placed within the default locations (e.g., `app/Models`) or adjust the paths accordingly if customized.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

This documentation provides an overview of the package functionality and usage. For detailed implementation and further customization, please refer to the source code.
