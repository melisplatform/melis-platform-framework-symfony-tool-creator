# melis-platform-frameworks-symfony-tool-creator
This Symfony module makes the business logic of creating Symfony tools

## Getting Started
These instructions will get you a copy of the project up and running on your machine.

### Prerequisites
You will need to install the following in order to have this module running:
* melisplatform/melis-platform-framework-symfony

It will automatically be done when using composer.

### Installing
Run the composer command:

```
composer require melisplatform/melis-platform-frameworks-symfony-tool-creator
```

### Activating the module
Activating this bundle is just the same way you activate your bundle inside symfony application. You just need to include its bundle class to the list of bundles inside symfony application (most probably in bundles.php file).

```
return [
    //All of the symfony activated bundles here
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    ...
    ...
    etc.
    //Melis Platform Custom Bundles
    MelisPlatformFrameworksSymfonyToolCreator\MelisPlatformFrameworksSymfonyToolCreatorBundle::class => ['all' => true]
];
```
## Authors

* **Melis Technology** - [www.melistechnology.com](https://www.melistechnology.com/)

See also the list of [contributors](https://github.com/melisplatform/melis-platform-framework-symfony/contributors) who participated in this project.


## License

This project is licensed under the OSL-3.0 License - see the [LICENSE](LICENSE)
