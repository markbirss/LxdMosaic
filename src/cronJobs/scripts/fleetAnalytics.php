#!/usr/bin/env php
<?php

$_ENV = getenv();

require __DIR__ . "/../../../vendor/autoload.php";

$builder = new \DI\ContainerBuilder();
$container = $builder->build();

$getAllContainers = $container->make("dhope0000\LXDClient\Tools\Instances\GetHostsInstances");
$getStorageDetails = $container->make("dhope0000\LXDClient\Tools\Storage\GetHostsStorage");
$storeDetails = $container->make("dhope0000\LXDClient\Model\Analytics\StoreFleetAnalytics");
$getClustersAndHosts = $container->make("dhope0000\LXDClient\Tools\Hosts\GetClustersAndStandaloneHosts");


/**
 * Storage pool data
 */

$storagePools = $getStorageDetails->getAll();

$totalStorageUsage = $storagePools["stats"]["storage"]["used"];
$totalStorageAvailable = $storagePools["stats"]["storage"]["total"];


/**
 * Memory usage
 */

$resources = $getClustersAndHosts->get(false);

$totalMemory = 0;

foreach ($resources["clusters"] as $cluster) {
    foreach ($cluster["members"] as $host) {
        if (!$host->hostOnline()) {
            continue;
        }
        $host = $host->getCustomProp("resources");
        $totalMemory += $host["memory"]["used"];
    }
}

foreach ($resources["standalone"]["members"] as $host) {
    if (!$host->hostOnline()) {
        continue;
    }

    $host = $host->getCustomProp("resources");

    $totalMemory += $host["memory"]["used"];
}

/**
 * Container details
 */

$containersByHost = $getAllContainers->getAll();

$activeContainers = 0;

foreach ($containersByHost as $host) {
    foreach ($host->getCustomProp("containers") as $container) {
        if ($container["state"]["status_code"] == 103) {
            $activeContainers++;
        }
    }
}

/**
 * Store results
 */

$storeDetails->store($totalMemory, $activeContainers, $totalStorageUsage, $totalStorageAvailable);

exit(0);
