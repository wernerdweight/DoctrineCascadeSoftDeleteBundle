DoctrineCascadeSoftDeleteBundle
====================================

A Symfony bundle that enables your application to cascade on delete even if you're using soft-delete.

[![Build Status](https://travis-ci.org/wernerdweight/DoctrineCascadeSoftDeleteBundle.svg?branch=master)](https://travis-ci.org/wernerdweight/DoctrineCascadeSoftDeleteBundle)
[![Latest Stable Version](https://poser.pugx.org/wernerdweight/doctrine-cascade-soft-delete-bundle/v/stable)](https://packagist.org/packages/wernerdweight/doctrine-cascade-soft-delete-bundle)
[![Total Downloads](https://poser.pugx.org/wernerdweight/doctrine-cascade-soft-delete-bundle/downloads)](https://packagist.org/packages/wernerdweight/doctrine-cascade-soft-delete-bundle)
[![License](https://poser.pugx.org/wernerdweight/doctrine-cascade-soft-delete-bundle/license)](https://packagist.org/packages/wernerdweight/doctrine-cascade-soft-delete-bundle)


Installation
------------

### 1. Download using composer

```bash
composer require wernerdweight/doctrine-cascade-soft-delete-bundle
```

### 2. Enable the bundle

Enable the bundle in your kernel:

```php
    <?php
    // config/bundles.php
    return [
        // ...
        WernerDweight\DoctrineCascadeSoftDeleteBundle\DoctrineCascadeSoftDeleteBundle::class => ['all' => true],
    ];
```

Configuration
------------

No configuration is required. This bundle automatically extends the default behaviour and soft-deletes any related entities. Use standard [Doctrine cascading options](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html#removing-entities) (`onDelete="CASCADE|DETACH"`). 

Trade-offs
------------

To be able to cascade soft-delete, each entity must have an identifier. M:N relationship thus needs to be refactored to an entity with two 1:N relationships. If an M:N related entity is attempted to be cascaded, an exception is thrown.

Each entity that should be able to be cascaded must also be soft-deletable (i.e. has `deletedAt` property). 

License
-------
This bundle is under the MIT license. See the complete license in the root directiory of the bundle.
