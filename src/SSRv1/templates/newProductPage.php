<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|null $base */

$base = $base ?? null;

$this->layout('main', ['title' => 'New product', 'itembuttons' => true]);
$this->insert('newProduct', [
    'base' => $base,
]);
