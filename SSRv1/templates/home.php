<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $locations */
/** @var int[] $recentlyAdded */
$this->layout('main', ['title' => 'Home', 'user' => $user]) ?>

<article>
	<h2>Hi</h2>
	<p>This is a temporary home page. Here are some stats.</p>
	<?php if(!empty($locations)): ?>
		<div class="statswrapper">
			<p>Available locations:</p>
			<table>
				<thead>
				<tr>
					<td>Location</td>
					<td>Items</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach($locations as $code => $count): ?>
					<tr>
						<td><?=$code?></td>
						<td><?=$count?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif;
	if(!empty($recentlyAdded)): ?>
		<div class="statswrapper">
			<p>Last <?= count($recentlyAdded) ?> items added:</p>
			<table class="home">
				<thead>
				<tr>
					<td>Item</td>
					<td>Added</td>
				</tr>
				</thead>
				<tbody>
				<?php date_default_timezone_set('Europe/Rome'); foreach($recentlyAdded as $code => $time): ?>
					<tr>
						<td><a href="/item/<?=$code?>"><?=$code?></a></td>
						<td><?=date('Y-m-d, H:i', $time)?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</article>
