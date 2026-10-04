<?php /** @var array $rows @var int $total @var string $status @var string $q @var int $page @var int $perPage */ ob_start();
$pages = max(1, (int) ceil($total / $perPage));
?>
<div class="row-between">
    <h1>Records <span class="muted">(<?= (int)$total ?>)</span></h1>
    <?php if (\App\Auth::can('record.export')): ?>
        <a class="btn btn-ghost" href="/records/export<?= $status!=='' ? '?status='.e($status) : '' ?>">Export CSV</a>
    <?php endif; ?>
</div>
<form method="get" action="/records" class="filterbar">
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['pending','verified','rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search NIK or name…">
    <button class="btn" type="submit">Filter</button>
</form>
<table class="table">
    <thead><tr><th>NIK</th><th>Name</th><th>Category</th><th>Status</th><th>Source</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): [$label,$cls]=status_badge($r['status']); ?>
        <tr>
            <td><code><?= e($r['nik']) ?></code></td>
            <td><?= e($r['full_name']) ?></td>
            <td><?= e($r['category'] ?? '—') ?></td>
            <td><span class="badge <?= e($cls) ?>"><?= e($label) ?></span></td>
            <td><?= e($r['source'] ?? '—') ?></td>
            <td><a href="/records/show?id=<?= (int)$r['id'] ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No records match.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php if ($pages > 1): $qs = fn($p) => '?status='.urlencode($status).'&q='.urlencode($q).'&page='.$p; ?>
<div class="pager">
    <?php if ($page>1): ?><a class="btn btn-ghost" href="<?= e($qs($page-1)) ?>">&larr; Prev</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page<$pages): ?><a class="btn btn-ghost" href="<?= e($qs($page+1)) ?>">Next &rarr;</a><?php endif; ?>
</div>
<?php endif; ?>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Records', 'content' => $content]);
