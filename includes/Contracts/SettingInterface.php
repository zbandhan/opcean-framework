<?php

namespace Giganteck\Opcean\Contracts;

interface SettingInterface {
    public function pageTitle(string $pageTitle): SettingInterface;
    public function menuTitle(string $menuTitle): SettingInterface;
    public function menuSlug(string $menuSlug): SettingInterface;
    public function capability(string $capability): SettingInterface;
    public function fields(array $section, array $fields): SettingInterface;
    public function render(): void;
}
