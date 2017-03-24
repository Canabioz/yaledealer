Установить :
composer require knplabs/knp-menu                 =    "knplabs/knp-menu-bundle": "^2.1.3",
composer require knplabs/knp-paginator-bundle     =    "knplabs/knp-paginator-bundle": "^2.5",
composer require whiteoctober/breadcrumbs-bundle  =    "whiteoctober/breadcrumbs-bundle": "^1.2"

Дописать в AppKernel :
 new Admin\ParserBundle\AdminParserBundle(),
 new Knp\Bundle\MenuBundle\KnpMenuBundle(),
 new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
 new WhiteOctober\BreadcrumbsBundle\WhiteOctoberBreadcrumbsBundle(),




Добавить в  # app/config/config.yml
knp_menu:
     # use "twig: false" to disable the Twig extension and the TwigRenderer
     twig:
         template: KnpMenuBundle::menu.html.twig
     #  if true, enables the helper for PHP templates
     templating: false
     # the renderer to use, list is also available by default
     default_renderer: twig

white_october_breadcrumbs: ~


Добавить в # app/config/routing.yml
admin_parser:
    resource: "@AdminParserBundle/Controller/"
    type:     annotation
    prefix:   /

Переместить папку public с src/Admin/ParserBundle в web/