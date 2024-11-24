## Autoloader

This repository contains a generic class autoloader that will load classes from a PSR-4 namespace root directory and an alternate directory/location for vendor or classmap of classes necessary for your plugin.

PRs welcome, especially for additional class aliases as you find needing them. Please refer to the readme in that folder for additional information on how to create these.

This is an alternative to composer's autoload creator.

```php
$root = [ 'My_Namespace' => 'path/to/my-namespace' ];
$classmap = [
    'Extra_Class' => 'path/to/extra-class.php',
    'AnotherClass' => 'path/to/class-another.php',
];

require_once 'Autoloader.php';
new \Fragen\Autoloader( $root, $classmap );
```

## Changelog
[CHANGES.md](CHANGES.md)

## Credits
Built by [Andy Fragen](https://thefragens.com/)
