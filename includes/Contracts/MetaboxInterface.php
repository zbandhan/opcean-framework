<?php

namespace Giganteck\Opcean\Contracts;

interface MetaboxInterface {
    public function id(string $id): MetaboxInterface;
    public function title(string $title): MetaboxInterface;
    public function screen(string $screen): MetaboxInterface;
    public function context(string $context): MetaboxInterface;
    public function priority(string $priority): MetaboxInterface;
    public function fields(array $fields): MetaboxInterface;
    public function render(): void;
}