<?php

declare(strict_types=1);

namespace AwesomeProject\Model\Manifest;

class ProjectSource
{
    private string $type;
    private string $path;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}