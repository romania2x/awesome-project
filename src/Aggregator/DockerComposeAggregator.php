<?php

declare(strict_types=1);

namespace AwesomeProject\Aggregator;

use AwesomeProject\Model\DockerCompose\Project;
use AwesomeProject\Model\DockerCompose\Volume;
use AwesomeProject\Model\ServiceConfiguration;
use JMS\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

class DockerComposeAggregator
{
    private Project $globalConfiguration;

    private Serializer $serializer;

    /**
     * @param string $path
     * @param Serializer $serializer
     */
    public function __construct(string $path, Serializer $serializer)
    {
        $this->globalConfiguration = new Project($path);
        $this->serializer = $serializer;
    }

    /**
     * Attempt service registration
     * @param ServiceConfiguration $serviceConfiguration
     */
    public function attemptRegistration(ServiceConfiguration $serviceConfiguration)
    {
        if (is_null($serviceConfiguration->getDockerComposeConfig())) {
            return;
        }

        /** @var Project $localConfig */
        $localConfig = $this->serializer->fromArray(
            Yaml::parseFile($serviceConfiguration->getDockerComposeConfig()),
            Project::class
        );

        foreach ($localConfig->getServices() as $serviceId => $service) {
            $service->setVolumes(array_map(
                function (Volume $volume) use ($serviceConfiguration) {
                    $hostPath = str_replace('./', $serviceConfiguration->getPath() . DIRECTORY_SEPARATOR, $volume->getHostPath());
                    return new Volume(
                        $hostPath,
                        $volume->getContainerPath()
                    );
                },
                $service->getVolumes()
            ));

            if (is_array($service->getEnvFile())) {
                $service->setEnvFile(array_map(
                    function (string $path) use ($serviceConfiguration) {
                        return str_replace('./', $serviceConfiguration->getPath() . DIRECTORY_SEPARATOR, $path);
                    },
                    $service->getEnvFile()
                ));
            }

            $this->globalConfiguration->setService($serviceId, $service);
        }

        foreach ($localConfig->getNetworks() as $networkId => $networkConfig) {
            $this->globalConfiguration->setNetwork($networkId, $networkConfig);
        }
    }

    /**
     * @return Project
     */
    public function getConfiguration(): Project
    {
        return $this->globalConfiguration;
    }
}
