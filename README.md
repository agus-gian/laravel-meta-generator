# Laravel Meta Generator

Laravel Meta Generator is a powerful package that enables you to easily attach and manage metadata for your Eloquent models without modifying their primary database tables. It provides a flexible key-value system featuring automatic type detection, casting, and handy artisan commands to simplify installation and maintenance.

---

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Attaching Metadata to a Model](#attaching-metadata-to-a-model)
  - [Managing Metadata](#managing-metadata)
- [Artisan Commands](#artisan-commands)
  - [`make:metadata`](#makemetadata)
  - [`metadata:clean-orphaned`](#metadataclean-orphaned)
- [Configuration](#configuration)
- [License](#license)

---

## Installation

There are two ways to integrate Laravel Meta Generator into your project:

### 1. Via Packagist

Run the following command:

```bash
composer require augustpermana/laravel-meta-generator
```

Laravel will automatically discover the service provider via package discovery. If necessary, manually add the provider in your `config/app.php`:

```php
'providers' => [
    // Other Service Providers

    August\MetaGenerator\ServiceProvider::class,
],
```

### 2. Using a Local Repository

If the package isn’t published on Packagist yet, add it as a local repository. Modify your project's `composer.json`:

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
composer require augustpermana/laravel-meta-generator
```

This configuration instructs Composer to use the specified local path for the package.

---

## Usage

Laravel Meta Generator lets you attach metadata to your models without modifying the original database tables. Once installed, you can either use the provided artisan commands or manually integrate the functionality into your models.

### Attaching Metadata to a Model

1. **Generate Metadata Files:**

   Run the artisan command to set up the metadata system for an existing model. For example, for a `Book` model:

   ```bash
   php artisan make:metadata --model=Book
   ```

   When you run this command, it performs the following actions:
   - **Creates a Meta Model File:** Generates a new file (e.g., `BookMeta.php`) in your application's `Models` directory. This file extends the package’s base `MetaModel` class.
   - **Updates the Original Model:** You must manually update your original model file (e.g., `Book.php`) to include the `HasMetadata` trait. For example:

     ```php
     <?php

     namespace App\Models;

     use Illuminate\Database\Eloquent\Model;
     use AugustPermana\MetaGenerator\Traits\HasMetadata;

     class Book extends Model
     {
         use HasMetadata;
     }
     ```

   - **Creates a Migration:** Generates a migration to create the corresponding metadata table (e.g., `book_meta`).

### Managing Metadata

Once set up, your model gains several useful methods via the `HasMetadata` trait:

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

- **Querying Models by Metadata:**

  ```php
  // Retrieve models with any value for the "author" metadata
  $models = Model::whereHasMeta('author')->get();

  // Retrieve models with a specific "author" value
  $models = Model::whereHasMeta('author', 'John Doe')->get();
  ```

---

## Artisan Commands

### `make:metadata`

This command sets up the metadata infrastructure for a specific model.

**Usage:**

```bash
php artisan make:metadata --model=ModelName
```

**What It Does:**

- Generates a new meta model file in `app/Models` (e.g., `ModelNameMeta.php`).
- Requires you to manually update your original model to include the `HasMetadata` trait.
- Creates a migration to build the metadata table (e.g., `model_name_meta`).

### `metadata:clean-orphaned`

This command cleans up metadata records that no longer have an associated parent model.

**Usage:**

```bash
php artisan metadata:clean-orphaned --model=ModelName
```

**What It Does:**

- Scans the metadata table for records with missing parent entries.
- Prompts for confirmation before deleting any orphaned records.
- Provides a summary of the changes performed.

---

## Configuration

No additional configuration is required. Simply run the artisan commands as needed. Ensure that your models are located in the default directory (e.g., `app/Models`) or adjust the paths accordingly if customized.

---

## License

Laravel Meta Generator is open-sourced software licensed under the [MIT license](LICENSE).

---

This documentation provides an overview of the package's functionality and usage. For more details and further customization options, please refer to the source code.
