<?php /** @var array $summary @var string $filename */ ob_start(); ?>
<h1>Import result</h1>
<div class="card">
    <p>File: <code><?= e($filename) ?></code></p>
    <div class="stats">
        <div class="stat"><span class="muted">Rows read</span><strong><?= (int)$summary['total'] ?></strong></div>
        <div class="stat"><span class="badge badge-green">Inserted</span><strong><?= (int)$summary['inserted'] ?></strong></div>
        <div class="stat"><span class="badge badge-amber">Duplicates</span><strong><?= (int)$summary['duplicates'] ?></strong></div>
        <div class="stat"><span class="badge badge-red">Invalid</span><strong><?= (int)$summary['invalid'] ?></strong></div>
    </div>
</div>
<?php if (!empty($summary['errors'])): ?>
<h2>Skipped rows</h2>
<div class="card">
    <ul class="errlist">
        <?php foreach ($summary['errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<div class="actions">
    <a class="btn" href="/records">View records</a>
    <a class="btn btn-ghost" href="/import">Import another file</a>
</div>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Import result', 'content' => $content]);
