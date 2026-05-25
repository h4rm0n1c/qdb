<?php
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This importer is CLI-only.\n");
	exit(1);
}

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/qdb/global.php';

function usage() {
	fwrite(STDERR, "Usage:\n");
	fwrite(STDERR, "  php tools/import_bash_tsv.php --file /path/to/compiled.tsv --dry-run\n");
	fwrite(STDERR, "  php tools/import_bash_tsv.php --file /path/to/compiled.tsv --commit\n");
	exit(1);
}

$opts = getopt('', array('file:', 'dry-run', 'commit', 'modid::'));
if (!isset($opts['file']) || (isset($opts['dry-run']) && isset($opts['commit'])) || (!isset($opts['dry-run']) && !isset($opts['commit']))) {
	usage();
}

$file = $opts['file'];
$dryRun = isset($opts['dry-run']);
$modid = isset($opts['modid']) ? (int) $opts['modid'] : 1;
if ($modid < 0) {
	usage();
}

if (!is_readable($file)) {
	fwrite(STDERR, "Input file is not readable.\n");
	exit(1);
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
	fwrite(STDERR, "DB FAIL: " . mysqli_connect_error() . "\n");
	exit(1);
}

mysqli_set_charset($conn, 'utf8mb4');

$selectStmt = mysqli_prepare($conn, "SELECT id FROM qdb WHERE id = ?");
$upsertStmt = mysqli_prepare(
	$conn,
	"INSERT INTO qdb (id, quote, rating, approved, modid, comment, flagged)
	VALUES (?, ?, ?, 1, ?, '', 0)
	ON DUPLICATE KEY UPDATE quote = VALUES(quote), rating = VALUES(rating), approved = 1, modid = VALUES(modid), comment = '', flagged = 0"
);

if (!$selectStmt || !$upsertStmt) {
	fwrite(STDERR, "DB FAIL: could not prepare import statements.\n");
	exit(1);
}

$handle = fopen($file, 'r');
if (!$handle) {
	fwrite(STDERR, "Input file could not be opened.\n");
	exit(1);
}

$rowsRead = 0;
$inserted = 0;
$updated = 0;
$skipped = 0;
$errors = 0;

while (($line = fgets($handle)) !== false) {
	$line = rtrim($line, "\r\n");
	if ($line === '') {
		$skipped++;
		continue;
	}

	$rowsRead++;
	if ($rowsRead === 1) {
		$line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
	}

	$parts = explode("\t", $line, 3);
	if (count($parts) !== 3) {
		$skipped++;
		$errors++;
		continue;
	}

	list($idRaw, $scoreRaw, $quoteRaw) = $parts;
	if (!ctype_digit($idRaw) || (int) $idRaw < 1 || !preg_match('/^-?\d+$/', $scoreRaw)) {
		$skipped++;
		$errors++;
		continue;
	}

	$id = (int) $idRaw;
	$score = (int) $scoreRaw;
	$quoteText = str_replace("\\n", "\n", $quoteRaw);
	$quoteText = str_replace(array("\r\n", "\r"), "\n", $quoteText);
	$quoteText = trim($quoteText);
	if ($quoteText === '') {
		$skipped++;
		continue;
	}

	$quoteHtml = nl2br(htmlspecialchars($quoteText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);

	mysqli_stmt_bind_param($selectStmt, 'i', $id);
	if (!mysqli_stmt_execute($selectStmt)) {
		$skipped++;
		$errors++;
		continue;
	}
	mysqli_stmt_store_result($selectStmt);
	$exists = mysqli_stmt_num_rows($selectStmt) > 0;
	mysqli_stmt_free_result($selectStmt);

	if ($dryRun) {
		if ($exists) {
			$updated++;
		} else {
			$inserted++;
		}
		continue;
	}

	mysqli_stmt_bind_param($upsertStmt, 'isii', $id, $quoteHtml, $score, $modid);
	if (!mysqli_stmt_execute($upsertStmt)) {
		$skipped++;
		$errors++;
		continue;
	}

	if ($exists) {
		$updated++;
	} else {
		$inserted++;
	}
}

fclose($handle);

echo "mode=" . ($dryRun ? "dry-run" : "commit") . "\n";
echo "rows read=" . $rowsRead . "\n";
echo "inserted=" . $inserted . "\n";
echo "updated=" . $updated . "\n";
echo "skipped=" . $skipped . "\n";
echo "errors=" . $errors . "\n";
