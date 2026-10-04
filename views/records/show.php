<?php /** @var array $record */ ob_start();
[$label,$cls]=status_badge($record['status']); $id=(int)$record['id']; $csrf=\App\Csrf::field();
?>
<div class="row-between">
    <h1>Record <code><?= e($record['nik']) ?></code></h1>
    <span class="badge <?= e($cls) ?>"><?= e($label) ?></span>
</div>
<div class="card">
    <table class="meta">
        <tr><th>NIK</th><td><?= e($record['nik']) ?></td></tr>
        <tr><th>Name</th><td><?= e($record['full_name']) ?></td></tr>
        <tr><th>Address</th><td><?= e($record['address'] ?? '—') ?></td></tr>
        <tr><th>Category</th><td><?= e($record['category'] ?? '—') ?></td></tr>
        <tr><th>Source</th><td><?= e($record['source'] ?? '—') ?></td></tr>
        <tr><th>Added by</th><td><?= e($record['author']) ?> on <?= e($record['created_at']) ?></td></tr>
        <?php if ($record['note']): ?><tr><th>Note</th><td><?= e($record['note']) ?></td></tr><?php endif; ?>
    </table>
</div>
<?php if ($record['status']==='pending' && (\App\Auth::can('record.verify') || \App\Auth::can('record.reject'))): ?>
<div class="actions">
    <?php if (\App\Auth::can('record.verify')): ?>
    <form method="post" action="/records/verify" class="inline">
        <?= $csrf ?><input type="hidden" name="id" value="<?= $id ?>">
        <input type="text" name="note" placeholder="Optional note" maxlength="500">
        <button class="btn btn-primary" type="submit">Verify</button>
    </form>
    <?php endif; ?>
    <?php if (\App\Auth::can('record.reject')): ?>
    <form method="post" action="/records/reject" class="inline">
        <?= $csrf ?><input type="hidden" name="id" value="<?= $id ?>">
        <input type="text" name="note" placeholder="Reason for rejection" maxlength="500">
        <button class="btn btn-danger" type="submit">Reject</button>
    </form>
    <?php endif; ?>
</div>
<?php else: ?>
    <p class="muted">This record is already <strong><?= e($record['status']) ?></strong>.</p>
<?php endif; ?>
<p><a href="/records">&larr; Back to records</a></p>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Record', 'content' => $content]);
