<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';

// Simple admin check - redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = Database::getInstance();
    
    // Get period from query string (default: 30 days)
    $period = $_GET['period'] ?? '30_days';
    $interval = '30 DAY';
    if ($period === '7_days') $interval = '7 DAY';
    if ($period === '1_year') $interval = '1 YEAR';
    
    // Calculate date threshold
    $dateThreshold = date('Y-m-d H:i:s', strtotime("-$interval"));
    
    // === STATS CALCULATIONS ===
    
    // Total Users
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $newUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= '$dateThreshold'")->fetchColumn();
    $userGrowth = $totalUsers > 0 ? round(($newUsers / $totalUsers) * 100) : 0;
    
    // Active Listings
    $totalListings = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published'")->fetchColumn();
    $newListings = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published' AND created_at >= '$dateThreshold'")->fetchColumn();
    $listingGrowth = $totalListings > 0 ? round(($newListings / $totalListings) * 100) : 0;
    
    // Active Bookings
    $totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active')")->fetchColumn();
    $newBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active') AND created_at >= '$dateThreshold'")->fetchColumn();
    $bookingGrowth = $totalBookings > 0 ? round(($newBookings / $totalBookings) * 100) : 0;
    
    // Total Revenue
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'success'")->fetchColumn();
    $newRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'success' AND created_at >= '$dateThreshold'")->fetchColumn();
    $revenueGrowth = $totalRevenue > 0 ? round(($newRevenue / $totalRevenue) * 100) : 0;
    
    // === ACTION QUEUE ===
    $actionQueue = [];
    
    // Pending properties > 24h
    $pendingProps = $pdo->query("
        SELECT id, name, created_at 
        FROM properties 
        WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($pendingProps as $p) {
        $actionQueue[] = [
            'entity' => 'Listing Review Delay',
            'desc' => "Property: {$p['name']}",
            'severity' => 'High',
            'time' => $p['created_at'],
            'action_link' => "property_view.php?id={$p['id']}",
            'action_text' => 'Review listing'
        ];
    }
    
    // Pending KYC
    $pendingKYC = $pdo->query("SELECT id, user_id, submitted_at FROM kyc WHERE status = 'pending' LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pendingKYC as $k) {
        $actionQueue[] = [
            'entity' => 'KYC Verification',
            'desc' => "User ID: {$k['user_id']} pending verification",
            'severity' => 'Medium',
            'time' => $k['submitted_at'],
            'action_link' => "kyc.php",
            'action_text' => 'Verify user'
        ];
    }
    
    // === ACTIVITIES ===
    $activities = [];
    
    // Recent users
    $recentUsers = $pdo->query("SELECT first_name, last_name, created_at FROM users ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach($recentUsers as $u) {
        $activities[] = [
            'title' => 'New user registration',
            'time' => $u['created_at'],
            'desc' => ($u['first_name'] ?? 'User') . " " . ($u['last_name'] ?? '') . " joined the platform.",
            'type' => 'user'
        ];
    }
    
    // Recent properties
    $recentProps = $pdo->query("
        SELECT p.name, u.first_name, u.last_name, p.created_at 
        FROM properties p 
        JOIN users u ON p.host_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($recentProps as $p) {
        $activities[] = [
            'title' => 'New listing added',
            'time' => $p['created_at'],
            'desc' => "The host {$p['first_name']}, has submitted '{$p['name']}' for verification.",
            'type' => 'property'
        ];
    }
    
    // Sort by time
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $activities = array_slice($activities, 0, 5);
    
    // === RECENT LISTINGS ===
    $recentListings = $pdo->query("
        SELECT p.*, u.first_name, u.last_name 
        FROM properties p 
        JOIN users u ON p.host_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    echo "<div style='background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;'><strong>Dashboard Error:</strong> " . htmlspecialchars($errorMsg) . "</div>";
    error_log("Dashboard error: " . $errorMsg);
    $totalUsers = $totalListings = $totalBookings = $totalRevenue = 0;
    $userGrowth = $listingGrowth = $bookingGrowth = $revenueGrowth = 0;
    $actionQueue = $activities = $recentListings = [];
}

// Period label for display
$periodLabel = $period === '30_days' ? 'last month' : ($period === '7_days' ? 'last week' : 'last year');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>36Homes Dashboard | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#005a92',
                        'bg-light': '#f8fafc',
                        border: '#eef1f4',
                        'text-secondary': '#667085',
                    },
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-white min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-[240px] fixed h-full bg-white z-50"></aside>

        <!-- Main Content -->
        <main class="flex-1 ml-[240px] bg-gray-50 min-h-screen p-6">
            <!-- Top Nav -->
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-3 bg-white px-4 py-2.5 rounded-xl border border-border w-full max-w-[450px]">
                    <i class="bi bi-search text-gray-400"></i>
                    <input type="text" placeholder="Search for users, listings or bookings..." class="w-full outline-none text-[15px] bg-transparent">
                </div>
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer">
                        <i class="bi bi-bell text-xl text-gray-600"></i>
                        <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard overview</h1>
                <form method="GET" class="relative inline-block">
                    <select name="period" onchange="this.form.submit()" class="appearance-none bg-white border border-border px-4 py-2 rounded-lg text-sm font-medium focus:outline-none pr-10 cursor-pointer">
                        <option value="30_days" <?= $period === '30_days' ? 'selected' : '' ?>>Last 30 days</option>
                        <option value="7_days" <?= $period === '7_days' ? 'selected' : '' ?>>Last 7 days</option>
                        <option value="1_year" <?= $period === '1_year' ? 'selected' : '' ?>>Last year</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                </form>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-2xl border border-border">
                    <div class="flex items-center gap-2 text-text-secondary text-[15px] mb-4">
                        <i class="bi bi-people"></i>
                        <span>Total users</span>
                    </div>
                    <div class="text-[32px] font-extrabold mb-2"><?= number_format($totalUsers) ?></div>
                    <div class="text-[13px]">
                        <span class="<?= $userGrowth >= 0 ? 'text-green-600' : 'text-red-500' ?> font-semibold">
                            <?= $userGrowth >= 0 ? '+' : '' ?><?= $userGrowth ?>% vs <?= $periodLabel ?>
                        </span>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-2xl border border-border">
                    <div class="flex items-center gap-2 text-text-secondary text-[15px] mb-4">
                        <i class="bi bi-list-ul"></i>
                        <span>Active listings</span>
                    </div>
                    <div class="text-[32px] font-extrabold mb-2"><?= number_format($totalListings) ?></div>
                    <div class="text-[13px]">
                        <span class="<?= $listingGrowth >= 0 ? 'text-green-600' : 'text-red-500' ?> font-semibold">
                            <?= $listingGrowth >= 0 ? '+' : '' ?><?= $listingGrowth ?>% vs <?= $periodLabel ?>
                        </span>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-2xl border border-border">
                    <div class="flex items-center gap-2 text-text-secondary text-[15px] mb-4">
                        <i class="bi bi-house-check"></i>
                        <span>Active bookings</span>
                    </div>
                    <div class="text-[32px] font-extrabold mb-2"><?= number_format($totalBookings) ?></div>
                    <div class="text-[13px]">
                        <span class="<?= $bookingGrowth >= 0 ? 'text-green-600' : 'text-red-500' ?> font-semibold">
                            <?= $bookingGrowth >= 0 ? '+' : '' ?><?= $bookingGrowth ?>% vs <?= $periodLabel ?>
                        </span>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-2xl border border-border">
                    <div class="flex items-center gap-2 text-text-secondary text-[15px] mb-4">
                        <i class="bi bi-cash-stack"></i>
                        <span>Total earnings</span>
                    </div>
                    <div class="text-[32px] font-extrabold mb-2">₦<?= number_format($totalRevenue) ?></div>
                    <div class="text-[13px]">
                        <span class="<?= $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-500' ?> font-semibold">
                            <?= $revenueGrowth >= 0 ? '+' : '' ?><?= $revenueGrowth ?>% vs <?= $periodLabel ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-[1.5fr_1fr] gap-6">
                <!-- Action Queue -->
                <div class="bg-white rounded-2xl border border-border p-6 h-fit">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold">Action queue</h2>
                        <i class="bi bi-arrow-clockwise text-xl text-gray-400 cursor-pointer"></i>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[12px] uppercase text-text-secondary border-b border-border">
                                    <th class="pb-3 font-medium">Action entity</th>
                                    <th class="pb-3 font-medium">Description</th>
                                    <th class="pb-3 font-medium">Severity</th>
                                    <th class="pb-3 font-medium">Time</th>
                                    <th class="pb-3 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <?php if (empty($actionQueue)): ?>
                                    <tr><td colspan="5" class="py-4 text-center text-gray-500">No pending actions</td></tr>
                                <?php else: ?>
                                    <?php foreach($actionQueue as $item): ?>
                                        <tr class="text-[14px]">
                                            <td class="py-4">
                                                <div class="font-bold text-gray-900"><?= htmlspecialchars($item['entity']) ?></div>
                                            </td>
                                            <td class="py-4 text-text-secondary w-1/3"><?= htmlspecialchars($item['desc']) ?></td>
                                            <td class="py-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full <?= $item['severity'] === 'High' ? 'bg-orange-400' : 'bg-blue-400' ?>"></span>
                                                    <span class="font-medium <?= $item['severity'] === 'High' ? 'text-orange-500' : 'text-blue-500' ?>"><?= $item['severity'] ?></span>
                                                </div>
                                            </td>
                                            <td class="py-4 text-gray-400 text-xs"><?= date('h:i A', strtotime($item['time'])) ?></td>
                                            <td class="py-4">
                                                <a href="<?= $item['action_link'] ?>" class="text-primary font-bold hover:underline"><?= $item['action_text'] ?></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Activities -->
                <div class="bg-white rounded-2xl border border-border p-6 h-fit">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold">Activities</h2>
                    </div>
                    <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-6">
                        <button class="flex-1 py-2 text-[14px] font-semibold rounded-lg bg-white shadow-sm">Today</button>
                        <button class="flex-1 py-2 text-[14px] font-semibold text-gray-500">Yesterday</button>
                        <button class="flex-1 py-2 text-[14px] font-semibold text-gray-500">This week</button>
                    </div>
                    <div class="space-y-4">
                        <?php if (empty($activities)): ?>
                            <div class="text-center text-gray-500 py-4">No recent activities</div>
                        <?php else: ?>
                            <?php foreach($activities as $act): ?>
                                <div class="flex gap-3 items-start p-4 rounded-xl border border-gray-100 bg-slate-50/50">
                                    <div class="w-8 h-8 rounded-full bg-white border border-gray-100 flex items-center justify-center shadow-sm shrink-0">
                                        <i class="bi <?= $act['type'] === 'property' ? 'bi-house' : 'bi-person' ?> text-gray-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-[14px] text-gray-900"><?= htmlspecialchars($act['title']) ?></span>
                                            <span class="text-[12px] text-gray-400"><?= date('h:i A', strtotime($act['time'])) ?></span>
                                        </div>
                                        <p class="text-[13px] text-text-secondary leading-tight"><?= htmlspecialchars($act['desc']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recently Added Listings -->
            <div class="bg-white rounded-2xl border border-border p-6 mt-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold">Recently added listing</h2>
                    <i class="bi bi-arrow-clockwise text-xl text-gray-400 cursor-pointer"></i>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[12px] uppercase text-text-secondary border-b border-border">
                                <th class="pb-3 font-medium">Listing details</th>
                                <th class="pb-3 font-medium">Location</th>
                                <th class="pb-3 font-medium">Host</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Price</th>
                                <th class="pb-3 font-medium text-right pr-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <?php if (empty($recentListings)): ?>
                                <tr><td colspan="6" class="py-4 text-center text-gray-500">No recently added listings</td></tr>
                            <?php else: ?>
                                <?php foreach($recentListings as $p): 
                                    $statusBadge = 'bg-yellow-50 text-yellow-600';
                                    if($p['status'] === 'published' || $p['status'] === 'active') $statusBadge = 'bg-green-50 text-green-600';
                                    if($p['status'] === 'rejected' || $p['status'] === 'archived') $statusBadge = 'bg-red-50 text-red-600';
                                ?>
                                    <tr class="text-[14px] hover:bg-slate-50 transition-colors">
                                        <td class="py-4 pr-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-12 w-12 rounded-lg bg-gray-200 overflow-hidden flex-shrink-0">
                                                    <img src="../api/placeholder_image.php" class="h-full w-full object-cover">
                                                </div>
                                                <div>
                                                    <span class="font-bold block text-gray-900 truncate max-w-[150px]"><?= htmlspecialchars($p['name'] ?? 'Untitled') ?></span>
                                                    <span class="text-[12px] text-gray-400">ID: LST-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 text-gray-600 text-sm whitespace-nowrap"><?= htmlspecialchars($p['city'] ?? '-') ?>, <?= htmlspecialchars($p['state'] ?? '-') ?></td>
                                        <td class="py-4 font-medium whitespace-nowrap"><?= htmlspecialchars($p['first_name']) ?> <?= htmlspecialchars($p['last_name']) ?></td>
                                        <td class="py-4">
                                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase <?= $statusBadge ?>"><?= $p['status'] ?></span>
                                        </td>
                                        <td class="py-4 font-bold text-gray-900 whitespace-nowrap">₦<?= number_format($p['price']) ?>/m</td>
                                        <td class="py-4 text-right pr-4">
                                            <a href="property_view.php?id=<?= $p['id'] ?>" class="text-gray-400 hover:text-primary transition-colors"><i class="bi bi-eye-fill"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Inject JWT token from session into localStorage for API calls
        <?php if (isset($_SESSION['jwt_token'])): ?>
            localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
        <?php endif; ?>
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
