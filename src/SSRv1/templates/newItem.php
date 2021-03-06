<?php
/** @var \WEEEOpen\Tarallo\Item|null $base */
/** @var int $importedFrom|null */
/** @var bool $recursion */
/** @var bool $innerrecursion */
/** @var string[] $subitems */

$base = $base ?? null;
$importedFrom = $importedFrom ?? null;
$innerrecursion = $innerrecursion ?? false;
if($base === null) {
	$subitems = [];
	$features = [];
	$product = null;
} else {
	$subitems = $base->getContent();
	$features = $base->getOwnFeatures();
	if($base instanceof \WEEEOpen\Tarallo\ItemWithProduct) {
		$product = $base->getProduct();
	} else {
		$product = null;
	}
}

// to display new inner items, set their $recursion and $innerrecursion to true
// .head is the beginning of the edit/new subtree, .root is the root of the entire tree
?>

<article class="container item new editing <?=$recursion ? '' : 'root'?> <?=$innerrecursion ? '' : 'head'?>">
	<header class="row">
		<h2 class="col-12"><label>Code: <input class="newcode" placeholder="Automatically generated"></label></h2>
		<?php if(isset($base) && $base->hasCode()): ?>
			<div class="inline-alert alert-info" role="alert"><i class="fa fa-info-circle"></i>️&nbsp;This is a copy of <span class="text-monospace"><?= $base->getCode() ?></span>, remember to change serial numbers, notes, working status, etc...</div>
		<?php unset($noticeFeature); endif; ?>

		<?php if(isset($importedFrom)): ?>
			<div class="inline-alert alert-info" role="alert"><i class="fa fa-info-circle"></i>️&nbsp;This item is generated from a bulk import</span></div>
		<?php endif; ?>
	</header>

	<nav class="itembuttons row mx-2 mt-2 justify-content-end">
		<?php if(!$innerrecursion): ?>
			<button class="btn btn-outline-primary btn-item col-4 col-sm-auto mr-auto cancel" role="button">
				<i class="fa fa-arrow-circle-left"></i>&nbsp;Cancel
			</button>
			<button class="btn btn-outline-primary btn-item col-4 col-sm-auto save" role="button"
					<?= isset($importedFrom) ? 'data-importId="' . (int) $importedFrom . '"' : '' ?>>
				💾&nbsp;Save
			</button>
		<?php else: ?>
			<button class="btn btn-outline-danger btn-item col-4 col-sm-auto removenew" role="button">
				❌&nbsp;Delete
			</button>
		<?php endif ?>
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto addnew" role="button">
			<i class="fa fa-plus-circle"></i>&nbsp;More
		</button>
	</nav>

	<?php if(!$innerrecursion && !$recursion): ?>
		<section class="setlocation form-inline">
			<label for="newparent" class="my-1 mr-2">Location</label>
			<input id="newparent" class="form-control mb-2 mr-sm-2">
		</section>
	<?php endif ?>

	<?php if($product !== null): ?>
		<section class="product features">
			<?php
			$this->insert('features', ['features' => $product->getFeatures()]);
			?>
		</section>
	<?php endif ?>

	<section class="own features editing">
		<?php
		$this->insert('featuresEdit', ['features' => $features]);
		?>
	</section>

	<section class="addfeatures">
		<label>Feature:
			<select class="allfeatures">
			</select></label>
		<button>Add</button>
	</section>

	<nav class="itembuttons secondary row mx-2 mt-2 justify-content-end">
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto removeemptyfeatures" title="Remove empty features" role="button">
			🧽&nbsp;Clean
		</button>
		<button class="btn btn-outline-primary btn-item col-4 col-sm-auto addnew" role="button">
			<i class="fa fa-plus-circle"></i>&nbsp;More
		</button>
    </nav>

	<section class="subitems">
		<?php
			// Used when cloning, empty otherwise
			foreach($subitems as $subitem) {
				$this->insert('newItem', ['recursion' => true, 'innerrecursion' => true, 'base' => $subitem]);
			}
		?>
	</section>
</article>
<?php if(!$innerrecursion) {
	?><script>const activate = true;</script><?php
	$this->insert('editor');
} ?>
