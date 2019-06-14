# GieEzToolbarBundle

Ez Platform 2 toolbar bundle

## Install

the bundle is currently being developed and only available on github. So clone the repo in src :

```bash
git clone https://github.com/jlchassaing/GieEzToolbarBundle.git toolbar
```


Load the bundle in app/AppKernel.php at the end of the registerBundles function :

```php
new Gie\EzToolbarBundle\GieEzToolbarBundle(),
```

The routing file must be loaded in app/config/routing.yml file :

```yaml
_gieeztoolbarRoutes:
    resource: "@GieEzToolbarBundle/Resources/config/routing.yml"
```

Add the toolbar edit to the pagelayout. 
Place this code where you want to display the toolbar. At the top of the page is recommended, other paces have not been tested yet.  

```twig
{{ render_esi(controller('GieEzToolbarBundle:Toolbar:render')) }}
```
Next step is to give the toolbar role policy to a user or a group. Once logged in on the
front page with the matching user, the toolbar should appear.


