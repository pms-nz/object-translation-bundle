# pms-nz/object-translation-bundle

This bundle, based on symfonycasts\object-translation-bundle, provides a simple way to translate Doctrine entities in Symfony applications.

The major changes are:
* The translations are done automatically through Doctrine event listeners.
There is no need to use a `translate` method.
* Translation fallbacks for a locale can be chained. For example `'es' -> 'it' -> 'en'`.

## Installation

### Install the bundle via Composer:

```bash
composer require pms-nz/object-translation-bundle
```

### Enable the bundle in your `config/bundles.php` file:

> [!NOTE]
> This step is not required if you are using Symfony Flex.

```php
return [
    // ...
    ObjectTranslationBundle::class => ['all' => true],
];
```

### Create the translation entity in your app:

> [!NOTE]
> You may give your class a name other than "Translation".

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use PmsNz\ObjectTranslationBundle\Model\AbstractTranslation;

#[ORM\Entity]
class Translation extends AbstractTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;
}
```

### Configure the entity in your `config/packages/object_translation.yaml` file:

> [!NOTE]
> If your translation entity has another class name, use that.

```yaml
pms-nz_object_translation:
    translation_class: App\Entity\Translation
```

### Create and run the migration to add the translation table:

```bash
symfony console make:migration
symfony console doctrine:migrations:migrate
```

## Marking Entities as Translatable

To mark an entity as translatable, use the `Translatable` attribute on the entity class
and the `TranslatableProperty` attribute on the fields you want to translate.

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use PmsNz\ObjectTranslationBundle\Mapping\Translatable;
use PmsNz\ObjectTranslationBundle\Mapping\TranslatableProperty;

#[ORM\Entity]
#[Translatable('product')]
class Product
{
    // ...

    #[ORM\Column(type: 'string', length: 255)]
    #[TranslatableProperty]
    public string $name;

    #[ORM\Column(type: 'text')]
    #[TranslatableProperty]
    public string $description;
}
```

## Usage

The translator listens to Doctrine events and automatically translates when a translatable entity is loaded.


## Managing Translations

The database table that extends `ĀbstractTranslation` has the following structure:

- `id`: Primary key (added by you)
- `object_type`: The *alias* defined in the `Translatable` attribute (e.g., `product`)
- `object_id`: The ID of the translated entity
- `locale`: The locale of the translation (e.g., `fr`)
- `field`: The entity property name being translated (e.g., `description`)
- `value`: The translated value

Each row represents a single property translation for a specific entity in a specific locale.

You can manage these translations yourself but two console commands are provided to help:

### `object-translation:export`
``
This command exports all entity translations, in your default locale, to a CSV file.

```bash
symfony console object-translation:export translations.csv
```

This will create a `translations.csv` file at the root of your project with the following structure:

```csv
type,id,field,value
```

You can then take this file to translation service for translation. Be sure to keep
the `type`, `id`, and `field` columns intact. The `value` column is what needs to be translated
into the desired language.

You may also add a `--locale` argument.
In this case extra columns will be added according to the fallbacks for the value provided for the `--locale` argument.


For instance, if the fallbacks for the 'es' locale is as follows:

```yaml
pms-nz_object_translation:
    translation_class: App\Entity\Translation
    fallbacks:
        es: [it, fr]
```

The column headers will be

```csv
type,id,field,value,en,fr,it,es
```

with the `fr`, `it` and `es` columns providing the translations given in the `translation_class` for the `fr`, `it` and `es` locales respectively.
<!--
### `object-translation:import`
[Under development]

This command imports translations from a CSV file created by the `export` command after
the `value` column has been translated.

```bash
symfony console object-translation:import translations_fr.csv fr
```

The first argument is the path to the CSV file, and the second argument is the locale
of the translations in that file.
-->
## Translation Caching

For performance, translations are cached. By default, they use your `cache.app` pool
and have no expiration time. This can be configured:

```yaml
pms-nz_object_translation:
    cache:
        pool: 'cache.object_translation' # a custom pool name
        ttl: 3600 # expire after one hour
```

### Translation Tags

If your cache pool supports *cache tagging*, tags are added to the cache keys. Two keys
are added:

- `object-translation`: All translations are tagged with this key.
- `object-translation-{type}`: Where `{type}` is the translatable alias (e.g., `product`).

You can invalidate these tags by using the `cache:pool:invalidate-tags` command:

```bash
# invalidate all object translation caches
symfony console cache:pool:invalidate-tags object-translation

# invalidate only the translation cache for "product" entities
symfony console cache:pool:invalidate-tags object-translation-product
```

<!--
### `object-translation:warmup` Command
[Under development]

This command preloads all translations into the cache for all your
app's enabled locales.

```bash
symfony console object-translation:warmup
```
-->

## Translation Fallback Chains

This logic allows you to support regional dialects or specific user groups without duplicating your entire translation library.

When using a base language (e.g., Spanish) across different groups, you can store only the unique variations rather than the entire language set.
This is achieved by "chaining" the translation lookup:

1. **Specific Variant:** The system first looks for the translation in the group-specific language (e.g., `es-gp1`).
2. **Next Language:** If the translation is missing, it falls back to the next language (`es`) in the chain. This step is repeated until a translation is found.
3. **Default Value:** If no translation is found in the chain, the system reverts to the global default language.

**Example Chain:** `'es-gp1' -> 'es' -> Default`


```yaml
pms-nz_object_translation:
    fallbacks:
        es-dl1: [es]
        es-gp1: [es-dl1, es]
```

Note that these fallbacks are indexed by the locale which begins the fallback chain.

## Full Default Configuration

```yaml
pms-nz_object_translation:

    # The class name of your Translation entity.
    translation_class:    ~ # Required, Example: App\Entity\Translation

    # Cache settings for object translations.
    cache:
        enabled:              true

        # The cache pool to use for storing object translations.
        pool:                 null

        # The time-to-live for cached translations, in seconds. null for no expiration.
        ttl:                  null

    # Fallbacks for object translations.
    fallbacks:

        # Prototype
        name:                 ~
```
