<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string[][] $todos */
$this->layout('main', ['title' => 'Stats: TODOs', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'todo']);
?>
<div class="row">
<?php foreach($todos as $feature => $items): ?>
    <?php if(count($items) > 0): ?>
	<div class="stats list col-12">
        <p><?= WEEEOpen\Tarallo\SSRv1\FeaturePrinter::printableValue(new \WEEEOpen\Tarallo\Feature('todo',
                $feature)) ?> (<?= count($items) ?> items, max 100 shown)</p>
        <div>
            <?php foreach($items as $item): ?>
                <a href="/item/<?= $this->e(rawurlencode($item)) ?>"><?= $this->e($item) ?></a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
<?php endforeach ?>
</div>
