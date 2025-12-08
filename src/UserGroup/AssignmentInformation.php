<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

class AssignmentInformation
{
    public function __construct(
        private ?string $type = null,
        private ?string $fromDate = null,
        private ?string $toDate = null,
        private array $recursiveMembership = []
    ) {}

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getFromDate(): ?string
    {
        return $this->fromDate;
    }

    public function getToDate(): ?string
    {
        return $this->toDate;
    }

    public function setRecursiveMembership(array $recursiveMembership): void
    {
        $this->recursiveMembership = $recursiveMembership;
    }

    public function getRecursiveMembership(): array
    {
        return $this->recursiveMembership;
    }
}
