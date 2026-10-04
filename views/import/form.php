<?php ob_start(); ?>
<h1>Import CSV</h1>
<div class="card">
    <p class="muted">Upload a CSV with the header row:
        <code>nik,full_name,address,category</code>.
        NIK must be 16 digits. Duplicate NIKs (already in the database or repeated in the
        file) are skipped automatically.</p>
    <form method="post" action="/import" enctype="multipart/form-data">
        <?= \App\Csrf::field() ?>
        <label>CSV file <input type="file" name="csv" accept=".csv" required></label>
        <button class="btn" type="submit">Upload &amp; import</button>
    </form>
    <p class="muted">A ready-made example lives in <code>sample_data/residents_sample.csv</code>
        (contains valid rows, a duplicate and an invalid row to demonstrate the checks).</p>
</div>
<?php $content = ob_get_clean();
echo view('layout', ['title' => $title ?? 'Import CSV', 'content' => $content]);
