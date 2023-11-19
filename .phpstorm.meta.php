<?php

namespace PHPSTORM_META {

    use support\Json;
    use support\Container;
    use function \app;

    override(
        \app(),
        map([
            'json' => \support\Json::class
        ])
    );

    override(
       \support\Container::make(),
        map([
            '' => '@'
        ])
    );

}
