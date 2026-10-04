<?php /** @var array $counts @var array $audit */ ob_start(); ?>
<h1>Dashboard</h1>
<div class="stats">
    <div class="stat"><span class="muted">Total</span><strong><?= (int) $counts['total'] ?></strong></div>
    <?php foreach (['pending','verified','rejected'] as $s): [$label,$cls]=status_badge($s); ?>
        <div class="stat"><span class="badge <?= e($cls) ?>"><?= e($label) ?></span><strong><?= (int)$counts[$s] ?></strong></div>
    <?php endforeach; ?>
</div>
<div class="actions">
    <a class="btn" href="/records">Browse records</a>
    <?php if (\App\Auth::can('record.import')): ?><a class="btn btn-ghost" href="/import">Import CSV</a><?php endif; ?>
    <?php if (\App\Auth::can('record.export')): ?><a class="btn btn-ghost" href="/records/export">Export all (CSV)</a><?php endif; ?>
</div>
<?php if (\App\Auth::can('audit.view')): ?>
<h2>Recent activity</h2>
<table class="table">
    <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($audit as $a): ?>
        <tr><td><?= e($a['created_at']) ?></td><td><?= e($a['actor']) ?></td>
        <td><?= e($a['action']) ?></td><td><?= e($a['entity']) ?> #<?= (int)$a['entity_id'] ?></td>
        <td><?= e($a['detail'] ?? '') ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$audit): ?><tr><td colspan="5" class="muted">No activity yet.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php endif; ?>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Dashboard', 'content' => $content]);
