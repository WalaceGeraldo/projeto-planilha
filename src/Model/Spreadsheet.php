<?php

namespace App\Model;

class Spreadsheet {
    private $id;
    private $name;
    private $ownerId;
    private $createdAt;
    private $updatedAt;

    public function __construct($id, $name, $ownerId, $createdAt, $updatedAt) {
        $this->id = $id;
        $this->name = $name;
        $this->ownerId = $ownerId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getOwnerId() { return $this->ownerId; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
}
