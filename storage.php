<?php
/**
 * TripSplit — تخزين البيانات في data/expenses.json
 *
 * - مجلد data/ محمي بملف .htaccess (Require all denied).
 * - كل حفظ يأخذ نسخة احتياطية في data/backups/ ويكتب مع LOCK_EX.
 * - ترحيل تلقائي: إذا وُجد expenses.json القديم في جذر المشروع ولم يوجد data/expenses.json، يُنسخ.
 */

function dataDir(): string {
    return __DIR__ . '/data';
}

function dataFile(): string {
    $cfg = function_exists('appConfig') ? appConfig() : (require __DIR__ . '/config.php');
    $rel = $cfg['data_file'] ?? 'data/expenses.json';
    return __DIR__ . '/' . ltrim($rel, '/');
}

function backupDir(): string {
    return dataDir() . '/backups';
}

function emptyData(): array {
    return ['team' => [], 'expenses' => [], 'contributions' => []];
}

function ensureDataDir(): void {
    foreach ([dataDir(), backupDir()] as $dir) {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('TripSplit: cannot create directory ' . $dir);
        }
    }
    $ht = dataDir() . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n", LOCK_EX);
    }
    // ترحيل من الإصدار القديم
    $legacy = __DIR__ . '/expenses.json';
    $file = dataFile();
    if (!is_file($file) && is_file($legacy)) {
        @copy($legacy, $file);
    }
}

function loadData(): array {
    ensureDataDir();
    $file = dataFile();
    if (!is_file($file)) {
        return normalizeData(emptyData());
    }
    $json = (string) @file_get_contents($file);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        error_log('TripSplit: invalid JSON in ' . $file);
        $data = emptyData();
    }
    return normalizeData($data);
}

function backupData(): void {
    $file = dataFile();
    if (is_file($file)) {
        $name = 'expenses_backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.json';
        @copy($file, backupDir() . '/' . $name);
    }
}

function saveData(array $data): bool {
    ensureDataDir();
    backupData();
    $json = json_encode(normalizeData($data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) return false;
    return @file_put_contents(dataFile(), $json, LOCK_EX) !== false;
}

/**
 * قراءة-تعديل-حفظ تحت قفل حصري حتى لا تضيع التعديلات المتزامنة.
 * $fn يستقبل البيانات ويعيد [data, error]. يُحفظ فقط إذا لم يكن هناك خطأ.
 */
function updateData(callable $fn): ?string {
    ensureDataDir();
    $lock = @fopen(dataDir() . '/.lock', 'c');
    if ($lock) flock($lock, LOCK_EX);
    try {
        [$data, $error] = $fn(loadData());
        if ($error !== null) return $error;
        if (!saveData($data)) return 'تعذر حفظ البيانات، تحقق من صلاحيات مجلد data/.';
        return null;
    } finally {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
