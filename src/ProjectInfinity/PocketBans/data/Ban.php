<?php

namespace ProjectInfinity\PocketBans\data;

class Ban {

    private $type;
    private $name;
    private $reason;
    private $banned_by;
    private $expires;
    private $created;
    private $xuid;

    public function __construct(string $name, int $type, string $banned_by, ?string $reason, string $xuid, $expires = null, $created = null) {
        $this->name = $name;
        $this->type = $type;
        $this->banned_by = $banned_by;
        $this->reason = $reason;
        $this->xuid = $xuid;
        $this->expires = $expires;
        $this->created = $created ?? time();
    }

    public function isPermanent(): bool {
        return $this->type !==  BanType::TEMP;
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function setXuid(string $xuid): void {
        $this->xuid = $xuid;
    }

    public function getType(): int {
        return $this->type;
    }

    public function setType(int $type): void {
        $this->type = $type;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getReason(): string {
        return $this->reason;
    }

    public function setReason(string $reason): void {
        $this->reason = $reason;
    }

    public function getBannedBy(): string {
        return $this->banned_by;
    }

    public function setBannedBy(string $banned_by): void {
        $this->banned_by = $banned_by;
    }

    public function getExpires(): ?int {
        return $this->expires;
    }

    public function setExpires(int $expires): void {
        $this->expires = $expires;
    }

    public function getCreated(): ?int {
        return $this->created;
    }

    public function setCreated(int $created): void {
        $this->created = $created;
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'banned_by' => $this->banned_by,
            'reason' => $this->reason,
            'expires' => $this->expires,
            'created' => $this->created,
            'xuid' => $this->xuid
        ];
    }

}