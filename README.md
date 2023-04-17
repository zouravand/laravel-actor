# Laravel Actor

A brand option to have specific action fields in model.


### Installation
In order to add the capability to your laravel application, you should require it via composer.

```shell
composer require tedon/laravel-actor
```

#### Publish configuration file
By publishing configuration file, it is possible to edit predefined custom shortcut macros.

```shell
php artisan vendor:publish --provider="Tedon\LaravelActor\Providers\ActorServiceProvider" --tag="actor-config"
```

### Basic Usage
You can use actor as a macro in migrations. Verb form of action is passed as the first argument.

```php
Schema::table('users', function (Blueprint $table) {
    ...
    $table->actor('test');
    ...
});
```

In the provided example, `$table->actor('test');` will create fields `tester_id` and `tester_type`

### Define Custom Shortcut Macros
use the provided config file to add or remove custom shortcut macros.

```php
return [
    'custom-macros' => [
        'edit',
        'approve',
    ]
];
```

In the provided example, the `create` macro is remove and the `approve` macro is added to the code;

### License
The Laravel Actor package is open-sourced software licensed under the MIT license.