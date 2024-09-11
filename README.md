# Smart Export Bundle.

On fly file data export management bundle

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require oh-deer-bundles/smart-export-bundle
```

Then you must create tables of this bundle.

```console
$ php bin/console make:migration
$ php bin/console doctrine:migration:migrate
```
Now you are able to create your first export. A basic admin interface are available on http://yourdomain.com/seb/admin. 
So you must secure this route you can change this in the file config/route/odb.yaml

You can build your own admin and export pages. In this case, don't forget to remove the odb.yaml routes files.

To build your own pages you can follow the AdminController included in the bundle.



