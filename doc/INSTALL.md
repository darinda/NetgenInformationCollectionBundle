NetgenInformationCollectionBundle installation instructions
===========================================================

Requirements
------------

* eZ Platform 1.0+
* eZ Publish 5

Installation steps
------------------

### Use Composer

Run the following from your website root folder to install Netgen InformationCollection Bundle:

```bash
$ composer require netgen/information-collection-bundle
```

### Activate the bundle

Activate the bundle in `app/AppKernel.php` file by adding it to the `$bundles` array in `registerBundles` method:

```php
public function registerBundles()
{
    ...
    $bundles[] = new Netgen\Bundle\InformationCollectionBundle\NetgenInformationCollectionBundle();

    return $bundles;
}
```

### Set siteaccess aware configuration

Here is sample configuration for actions, the developer will need to define a list of actions to be run depending on the content type.
Configuration needs to be added in `app/config/config.yml` or `app/config/ezplatform.yml`:

```yaml
netgen_information_collection:
   system:
       default:
           action_config:
              email:
                  templates:
                      default: 'AcmeBundle:email:default.html.twig'
                  default_variables:
                      sender: 'sender@example.com'
                      recipient: 'recipient@example.com'
                      subject: 'Subject'
           actions:
              default:
                  - email
                  - database
```

Don't forget to create default email template. 

### Clear the caches

Clear the eZ Publish caches with the following command:

```bash
$ php app/console cache:clear
```