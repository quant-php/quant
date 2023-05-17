# quant/phpstan

[phpstan](https://phpstan.org/) extensions for **quant**.

## Installation 

The extension is already available with [quant/quant](https://github.com/quant-php/quant). 

To register this extension with your project, use

```bash
$ composer require --dev phpstan/extension-installer && \
  composer require --dev quant/phpstan
```

This should automatically register the extension with your **phpstan** installation:

```neon
includes:
	- vendor/quant/phpstan
```

## Documentation

**quant/phpstan** provides support for properly analysing classes that use `getter` / `setter` automation with the help
of [**Quant\\Core\\Trait\\AccessorTrait**](https://quant-php.dev/docs/packages/quant/core#1-automated-gettersetter-creation).

```php

#[Getter]
#[Setter(Modifier::PROTECTED)]
class A
{
    private string $value;
}
```

will register with availability of `A`:

```
@method A setValue(string $value)
@method string getValue() 
```

Modifiers are considered: `setValue()` will be registered with `protected` access in this case.


## Resources

* [Report issues](https://github.com/quant-php/quant/issues) and
  [send Pull Requests](https://github.com/quant-php/quant/pulls)
  in the [main quant repository](https://github.com/quant-php/quant)
