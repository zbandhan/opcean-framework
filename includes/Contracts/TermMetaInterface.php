<?php

namespace Giganteck\Opcean\Contracts;

interface TermMetaInterface {
    public function fields($taxonomies, array $fields): TermMetaInterface;
}
