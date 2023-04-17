# Laravel Actor

A brand option to have specific action fields in model.


### Installation

```shell
composer require tedon/laravel-actor
```

### Basic Usage
use actor as a macro in migrations.
Use verb of action as the first argument.

```php
Schema::table('users', function (Blueprint $table) {
    ...
    $table->actor('test');
    ...
});
```

In the provided example, `$table->actor('test');` will create fields `tester_id` and `tester_type`