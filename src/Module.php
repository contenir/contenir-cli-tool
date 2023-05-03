<?php

namespace Contenir\Cli\Tool;

class Module
{
    /**
     *
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencyConfig(),
            'laminas-cli'    => $provider->getCliConfig()
        ];
    }
}
