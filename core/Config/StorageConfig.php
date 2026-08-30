<?php
// Path: core/Config/StorageConfig.php

namespace Core\Config;

class StorageConfig
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getDefaultDisk(): string
    {
        return $this->config->get('storage.disk', 'local');
    }

    public function getLocalPath(): string
    {
        return $this->config->get('storage.local.path', __DIR__ . '/../../storage/app/public');
    }

    public function getS3Bucket(): string
    {
        return $this->config->get('storage.s3.bucket', '');
    }
}