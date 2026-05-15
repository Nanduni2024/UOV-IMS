<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$page_title = 'Add Asset';
include 'header.php';
include 'sidebar.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asset_id = isset($_POST['asset_id']) ? $conn->real_escape_string($_POST['asset_id']) : '';
    $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
    $category = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : '';
    $location = isset($_POST['location']) ? $conn->real_escape_string($_POST['location']) : '';
    $purchase_date = isset($_POST['purchase_date']) ? $conn->real_escape_string($_POST['purchase_date']) : '';
    $unit_price = isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : 0;
    $condition = isset($_POST['condition']) ? $conn->real_escape_string($_POST['condition']) : '';
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';

    // Validation
    if (empty($asset_id) || empty($name) || empty($category) || empty($location) || empty($purchase_date) || empty($condition) || empty($status)) {
        $error_msg = "All required fields must be filled.";
    } elseif ($unit_price < 0) {
        $error_msg = "Unit price must be a positive number.";
    } else {
        // Check if asset_id already exists
        $check_result = $conn->query("SELECT id FROM assets WHERE asset_id = '$asset_id'");
        if ($check_result && $check_result->num_rows > 0) {
            $error_msg = "Asset ID already exists. Please use a different ID.";
        } else {
            // Insert asset using direct query - escape reserved keywords with backticks
            $insert_query = "INSERT INTO assets (asset_id, name, category, location, purchase_date, unit_price, `condition`, status) VALUES ('$asset_id', '$name', '$category', '$location', '$purchase_date', $unit_price, '$condition', '$status')";
            
            if ($conn->query($insert_query)) {
                $success_msg = "Asset added successfully!";
                log_audit($_SESSION['user_id'], 'add_asset', "Added asset: $asset_id - $name");
                // Clear form after success
                $_POST = array();
            } else {
                $error_msg = "Error adding asset: " . $conn->error;
            }
        }
    }
}
?>

<main class="flex-1 p-8 overflow-y-auto bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
            <span class="material-symbols-outlined text-5xl text-primary">add_box</span>
            Add New Asset
        </h1>
        <p class="text-slate-400 text-lg">Register a new asset in the inventory management system</p>
    </div>

    <!-- Messages -->
    <?php if ($success_msg): ?>
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-emerald-500/20 to-emerald-600/10 text-emerald-300 border border-emerald-500/50 flex items-start gap-3 animate-fade-in">
            <span class="material-symbols-outlined text-xl flex-shrink-0 mt-0.5">check_circle</span>
            <div>
                <p class="font-semibold"><?php echo $success_msg; ?></p>
                <a href="asset_registry.php" class="text-sm underline hover:text-emerald-200 transition mt-2 inline-block">← Back to Asset Registry</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-500/20 to-red-600/10 text-red-300 border border-red-500/50 flex items-start gap-3 animate-fade-in">
            <span class="material-symbols-outlined text-xl flex-shrink-0 mt-0.5">error</span>
            <div>
                <p class="font-semibold"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add Asset Form -->
    <div class="bg-gradient-to-br from-slate-800/60 to-slate-900/40 backdrop-blur-xl rounded-2xl border border-slate-700/50 shadow-2xl overflow-hidden">
        <div class="p-8 border-b border-slate-700/50 bg-gradient-to-r from-slate-800/80 to-slate-900/80">
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <span class="material-symbols-outlined text-primary">inventory_2</span>
                Asset Details
            </h2>
        </div>
        <form method="POST" class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Asset ID -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Asset ID *</label>
                    <div class="relative">
                        <input type="text" name="asset_id" required 
                            placeholder="e.g., AST-001"
                            value="<?php echo isset($_POST['asset_id']) ? htmlspecialchars($_POST['asset_id']) : ''; ?>"
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition">
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg">inventory</span>
                    </div>
                </div>

                <!-- Asset Name -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Asset Name *</label>
                    <div class="relative">
                        <input type="text" name="name" required 
                            placeholder="e.g., Dell Laptop"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition">
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg">label</span>
                    </div>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Category *</label>
                    <div class="relative">
                        <select name="category" required 
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition cursor-pointer appearance-none">
                            <option value="">Select Category</option>
                            <option value="IT Equipment" <?php echo (isset($_POST['category']) && $_POST['category'] === 'IT Equipment') ? 'selected' : ''; ?>>IT Equipment</option>
                            <option value="Furniture" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Furniture') ? 'selected' : ''; ?>>Furniture</option>
                            <option value="Laboratory" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Laboratory') ? 'selected' : ''; ?>>Laboratory</option>
                            <option value="Building" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Building') ? 'selected' : ''; ?>>Building</option>
                            <option value="Vehicle" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Vehicle') ? 'selected' : ''; ?>>Vehicle</option>
                            <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg pointer-events-none">category</span>
                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-slate-400 text-lg pointer-events-none">expand_more</span>
                    </div>
                </div>

                <!-- Location -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Location *</label>
                    <div class="relative">
                        <input type="text" name="location" required 
                            placeholder="e.g., Admin Office, Building A"
                            value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition">
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg">location_on</span>
                    </div>
                </div>

                <!-- Purchase Date -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Purchase Date *</label>
                    <div class="relative">
                        <input type="date" name="purchase_date" required 
                            value="<?php echo isset($_POST['purchase_date']) ? htmlspecialchars($_POST['purchase_date']) : ''; ?>"
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition">
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg">calendar_today</span>
                    </div>
                </div>

                <!-- Unit Price -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Unit Price ($) *</label>
                    <div class="relative">
                        <input type="number" name="unit_price" required step="0.01" 
                            placeholder="0.00"
                            value="<?php echo isset($_POST['unit_price']) ? htmlspecialchars($_POST['unit_price']) : ''; ?>"
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition">
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg">attach_money</span>
                    </div>
                </div>

                <!-- Condition -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Condition *</label>
                    <div class="relative">
                        <select name="condition" required 
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition cursor-pointer appearance-none">
                            <option value="">Select Condition</option>
                            <option value="excellent" <?php echo (isset($_POST['condition']) && $_POST['condition'] === 'excellent') ? 'selected' : ''; ?>>Excellent</option>
                            <option value="good" <?php echo (isset($_POST['condition']) && $_POST['condition'] === 'good') ? 'selected' : ''; ?>>Good</option>
                            <option value="fair" <?php echo (isset($_POST['condition']) && $_POST['condition'] === 'fair') ? 'selected' : ''; ?>>Fair</option>
                        </select>
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg pointer-events-none">check_circle</span>
                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-slate-400 text-lg pointer-events-none">expand_more</span>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Status *</label>
                    <div class="relative">
                        <select name="status" required 
                            class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition cursor-pointer appearance-none">
                            <option value="">Select Status</option>
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="retired" <?php echo (isset($_POST['status']) && $_POST['status'] === 'retired') ? 'selected' : ''; ?>>Retired</option>
                        </select>
                        <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg pointer-events-none">info</span>
                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-slate-400 text-lg pointer-events-none">expand_more</span>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-8 form-group">
                <label class="block text-sm font-bold text-slate-200 mb-3 uppercase tracking-wide">Description</label>
                <div class="relative">
                    <textarea name="description" rows="5" 
                        placeholder="Additional details about the asset..."
                        class="w-full px-4 py-3 pl-10 rounded-lg bg-slate-900/50 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition resize-none"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <span class="material-symbols-outlined absolute left-3 top-3.5 text-slate-400 text-lg pointer-events-none">description</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4">
                <button type="submit" class="flex-1 md:flex-none px-8 py-3 bg-gradient-to-r from-primary to-blue-600 text-white font-bold rounded-lg hover:shadow-lg hover:shadow-primary/50 transition flex items-center justify-center gap-2 group">
                    <span class="material-symbols-outlined text-lg group-hover:scale-110 transition">save</span>
                    Save Asset
                </button>
                <a href="asset_registry.php" class="flex-1 md:flex-none px-8 py-3 bg-slate-700/50 hover:bg-slate-700 text-white font-bold rounded-lg transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">close</span>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>