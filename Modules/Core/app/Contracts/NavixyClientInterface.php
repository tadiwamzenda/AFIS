<?php

namespace Modules\Core\Contracts;

interface NavixyClientInterface
{
    public function authenticate(string $email, string $password): array;
    public function get(string $endpoint, array $params = []): array;
    public function post(string $endpoint, array $data = []): array;
    public function refreshHash(): bool;
    public function getHash(): ?string;
}