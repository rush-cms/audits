<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class Url implements Stringable
{
    private string $value;

    public function __construct(string $url)
    {
        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid URL: {$url}");
        }

        $this->value = $url;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getHost(): string
    {
        return (string) parse_url($this->value, PHP_URL_HOST);
    }
}
