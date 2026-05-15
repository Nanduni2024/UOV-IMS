<?php
// config.php already starts the session
include 'config.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$page_title = 'Edit Asset';
include 'header.php';
include 'sidebar.php';

$success_msg = '';
$error_msg = '';
$asset = null;

/* =========================
   GET ASSET ID
========================= */
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* =========================
   FETCH ASSET
========================= */
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    $error_msg = "Asset not found!";
}

/* =========================
   UPDATE ASSET
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_asset']) && $asset) {

    $asset_id_val  = trim($_POST['asset_id']);
    $name          = trim($_POST['name']);
    $category      = trim($_POST['category']);
    $location      = trim($_POST['location']);
    $purchase_date = $_POST['purchase_date'];
    $unit_price    = floatval($_POST['unit_price']);
    $condition     = $_POST['condition'];
    $status        = $_POST['status'];
    $documentation = trim($_POST['description']);

    $stmt = $conn->prepare("
        UPDATE assets 
        SET asset_id = ?, 
            name = ?, 
            category = ?, 
            location = ?, 
            purchase_date = ?, 
            unit_price = ?, 
            `condition` = ?, 
            status = ?, 
            documentation = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssdsssi",
        $asset_id_val,
        $name,
        $category,
        $location,
        $purchase_date,
        $unit_price,
        $condition,
        $status,
        $documentation,
        $asset_id
    );

    if ($stmt->execute()) {
        $success_msg = "Asset updated successfully!";

        // Reload updated asset
        $stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
        $stmt->bind_param("i", $asset_id);
        $stmt->execute();
        $asset = $stmt->get_result()->fetch_assoc();
    } else {
        $error_msg = "Update failed: " . $stmt->error;
    }
}
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">

    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Edit Asset</h1>
        <p class="text-slate-400">Modify asset information</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="mb-6 p-4 rounded bg-emerald-600 text-white">
            <?= htmlspecialchars($success_msg) ?>
            <a href="asset_registry.php" class="underline ml-3">Back to Assets</a>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="mb-6 p-4 rounded bg-red-600 text-white">
            <?= htmlspecialchars($error_msg) ?>
            <a href="asset_registry.php" class="underline ml-3">Back</a>
        </div>
    <?php endif; ?>

<?php if ($asset): ?>
<form method="POST" class="bg-slate-800 p-6 rounded-lg shadow-lg">

    <input type="hidden" name="update_asset" value="1">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div>
            <label class="text-white font-semibold">Asset ID</label>
            <input type="text" name="asset_id" required
                value="<?= htmlspecialchars($asset['asset_id']) ?>"
                class="w-full p-2 rounded bg-slate-700 text-white">
        </div>

        <div>
            <label class="text-white font-semibold">Asset Name</label>
            <input type="text" name="name" required
                value="<?= htmlspecialchars($asset['name']) ?>"
                class="w-full p-2 rounded bg-slate-700 text-white">
        </div>

        <div>
            <label class="text-white font-semibold">Category</label>
            <input type="text" name="category"
                value="<?= htmlspecialchars($asset['category']) ?>"
                class="w-full p-2 rounded bg-slate-700 text-white">
        </div>

        <div>
            <label class="text-white font-semibold">Location</label>
            <input type="text" name="location"
                value="<?= htmlspecialchars($asset['location']) ?>"
                class="w-full p-2 rounded bg-slate-700 text-white">
        </div>

        <div>
            <label class="text-white font-semibold">Purchase Date</label>
            <input type="date" name="purchase_date"
                value="<?= $asset['purchase_date'] ?>"
                class="w-full p-2 rounded bg-slate-700 text-white">
        </div>

        <div>
            <label class="text-white font-semibold">Unit Price</label>
            <input type="number" step="0.01" name="unit_price"
                value="<?= $asset['unit_price'] ?>"
                class="w-full p-2 rounded bg-slate-700 text-white">
        </div>

        <div>
            <label class="text-white font-semibold">Condition</label>
            <select name="condition" class="w-full p-2 rounded bg-slate-700 text-white">
                <option value="new" <?= $asset['condition']=='new'?'selected':'' ?>>New</option>
                <option value="good" <?= $asset['condition']=='good'?'selected':'' ?>>Good</option>
                <option value="refurbished" <?= $asset['condition']=='refurbished'?'selected':'' ?>>Refurbished</option>
                <option value="maintenance_required" <?= $asset['condition']=='maintenance_required'?'selected':'' ?>>Maintenance Required</option>
            </select>
        </div>

        <div>
            <label class="text-white font-semibold">Status</label>
            <select name="status" class="w-full p-2 rounded bg-slate-700 text-white">
                <option value="active" <?= $asset['status']=='active'?'selected':'' ?>>Active</option>
                <option value="maintenance" <?= $asset['status']=='maintenance'?'selected':'' ?>>Maintenance</option>
                <option value="disposed" <?= $asset['status']=='disposed'?'selected':'' ?>>Disposed</option>
            </select>
        </div>

    </div>

    <div class="mt-6">
        <label class="text-white font-semibold">Documentation / Description</label>
        <textarea name="description" rows="4"
            class="w-full p-2 rounded bg-slate-700 text-white"><?= htmlspecialchars($asset['documentation'] ?? '') ?></textarea>
    </div>

    <div class="mt-6 flex gap-4">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded text-white">
            Save Changes
        </button>
        <a href="asset_registry.php" class="bg-gray-600 px-6 py-2 rounded text-white">
            Cancel
        </a>
    </div>

</form>
<?php endif; ?>

</main>

<?php include 'footer.php'; ?>
