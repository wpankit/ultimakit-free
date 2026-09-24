<?php
class UltimaKit_Module_Base {
    protected $id;
    protected $name;
    protected $description;
    protected $category;
    protected $plan;
    protected $type;
    protected $link;
    protected $is_active;
    protected $settings;
    protected $settings_link;

    public function __construct($data) {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->plan = $data['plan'] ?? 'free';
        $this->type = $data['type'] ?? '';
        $this->link = $data['link'] ?? '';
        $this->is_active = $data['is_active'] ?? false;
        $this->settings = $data['settings'] ?? null;
        $this->settings_link = $data['settings_link'] ?? '#';
    }

    public function getID() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getPlan() {
        return $this->plan;
    }

    public function getType() {
        return $this->type;
    }

    public function getLink() {
        return $this->link;
    }

    public function isActive() {
        return $this->is_active;
    }

    public function getSettings() {
        return $this->settings;
    }

    public function getSettingsLink() {
        return $this->settings_link;
    }
}
