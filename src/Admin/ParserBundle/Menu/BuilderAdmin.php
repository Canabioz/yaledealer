<?php
// src/AppBundle/Menu/Builder.php
namespace Admin\ParserBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class BuilderAdmin implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root')->setChildrenAttribute('class', 'nav nav-sidebar');
        $menu->addChild('Home', array('route' => 'dateparsing','routeParameters' => ['id' => $options['dateparsing']->getId()]));
        $menu->addChild('DateParsing', array('route' => 'homepage'));
        $menu->addChild('Sections', array('route' => 'sections', 'routeParameters' => ['id' => $options['dateparsing']->getId()]));
        $menu->addChild('Downloads', array('route' => 'downloads', 'routeParameters' => ['id' => $options['dateparsing']->getId()]));

        return $menu;
    }
}