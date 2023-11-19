<?php
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(config('dependence',[]));
if (method_exists(\DI\ContainerBuilder::class, "useAnnotations")) {
    $builder->useAnnotations(true);
}
if (method_exists(\DI\ContainerBuilder::class, "useAttributes")) {
    $builder->useAttributes(true);
}
$builder->useAutowiring(true);
return $builder->build();
