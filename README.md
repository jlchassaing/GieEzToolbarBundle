# GieEzToolbarBundle

Ez Platform 2 toolbar bundle

## Install

the bundle is currently being developed and only available on github. So clone the repo in src :

```bash
git clone https://github.com/jlchassaing/GieEzToolbarBundle.git toolbar
```

### Autoload

You may add the psr-4 composer autoload :
```json
            "Gie\\EzToolbar\\" : "src/gietoolbar/src/lib/",
            "Gie\\EzToolbarBundle\\": "src/gietoolbar/src/bundle/"
```

### Add to kernel

Load the bundle in app/AppKernel.php at the end of the registerBundles function :

```php
new Gie\EzToolbarBundle\GieEzToolbarBundle(),
```

### Add routing

The routing file must be loaded in app/config/routing.yml file :

```yaml
_gieeztoolbarRoutes:
    resource: "@GieEzToolbarBundle/Resources/config/routing.yml"
```

### Build scss

Build the scss with command :
```bash
yarn encore dev
```
 
### Display the toolbar

Add the toolbar edit to the pagelayout.html.twig 
Place this code where you want to display the toolbar. At the top of the page is recommended, other paces have not been tested yet.  

```twig
{{ ezToolbar(location is defined ? location : null) }}
```

### Set user rights 

If you use and specific user instead of admin you need to set the user policy.

Give the toolbar role policy to a user or a group whit no limitations. Once logged in on the
front page with the matching user, the toolbar should appear.

## Features

You should be able to create, edit and cancel (create and edit).
Many things to do :
 - filter classes according to user rights :
    That is available in current master and will be used when in stable release.
    
 - the create new draft code to edit content is not satisfying. Needs some refactoring.
 
 - write feature and phpunit testing    
