<aside class="w-64 flex-shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col">
    <nav class="flex-1 p-4 flex flex-col gap-1 overflow-y-auto">
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-pointer transition-colors" href="dashboard.php">Dashboard</a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-pointer transition-colors" href="asset_registry.php">Asset Registry</a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-pointer transition-colors" href="maintenance.php">Maintenance</a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-pointer transition-colors" href="reports.php">Reports</a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-pointer transition-colors" href="audit_logs.php">Audit Logs</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <div class="pt-4 mt-4 border-t border-slate-700">
            <span class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 block mb-2">Admin Tools</span>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-pointer transition-colors" href="user_management.php">Manage Users</a>
        </div>
        <?php endif; ?>
    </nav>
</aside>