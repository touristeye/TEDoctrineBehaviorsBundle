Install
-------

It's always the same

**Symfony 2.0.***

*/deps*
```
[TETranslationBundle]
    git=git://github.com/touristeye/TranslationBundle.git
    target=bundles/TE/TranslationBundle
```

*/app/autoload.php*
```php
<?php
$loader->registerNamespaces(array(
    // other namespaces
    'TE' => __DIR__.'/../vendor/bundles',
));
```

then

```sh
$ ./bin/vendors install
```

**Symfony 2.1.***

*composer.json*
```json
{
    "require": {
        "touristeye-translation-bundle": "dev-master"
    }
}
```

Remember to add the minimum stability directive, because this bundle is still in alpha state

*composer.json (root)*
```json
{
    "minimum-stability": "dev"
}
```

then

```sh
$ curl -s http://getcomposer.org/installer | php
$ php composer.phar install
```

**symfony 2.0.* AND Symfony 2.1.***

*/app/AppKernel.php*
```php
<?php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // other bundles
            new TE\TranslationBundle\TETranslationBundle()
        );
    }
}
```