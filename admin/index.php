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
<body class="bg-[#F9FAFB] min-h-screen font-outfit">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-[260px] fixed h-full bg-white z-50"></aside>

        <!-- Main Content -->
        <main class="flex-1 ml-[260px] min-h-screen p-8">
            <!-- Top Nav -->
            <div class="flex justify-between items-center mb-10">
                <div class="text-[14px] text-gray-400">
                    Dashboard overview
                </div>
                <div class="flex-1 max-w-[500px] mx-8">
                    <div class="relative">
                        <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" placeholder="Search for users, listings or bookings..." 
                               class="w-full bg-white border border-gray-100 rounded-xl py-3 pl-12 pr-4 text-[14px] focus:outline-none focus:ring-2 focus:ring-primary/5 shadow-sm transition-all">
                    </div>
                </div>
                <div class="w-10"></div>
            </div>

            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-[32px] font-bold text-gray-900 mb-2">Welcome back, Admin</h1>
                    <p class="text-gray-400 text-[15px]">Here's what's happening with your platform today.</p>
                </div>
                <form method="GET" class="relative">
                    <select name="period" onchange="this.form.submit()" class="appearance-none bg-white border border-gray-100 px-6 py-3 rounded-xl text-[14px] font-bold text-gray-700 focus:outline-none pr-12 cursor-pointer shadow-sm">
                        <option value="30_days" <?= $period === '30_days' ? 'selected' : '' ?>>Last 30 days</option>
                        <option value="7_days" <?= $period === '7_days' ? 'selected' : '' ?>>Last 7 days</option>
                        <option value="1_year" <?= $period === '1_year' ? 'selected' : '' ?>>Last year</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </form>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <?= dashboardStatCard('Total users', number_format($totalUsers), $userGrowth, 'bi-people') ?>
                <?= dashboardStatCard('Active listings', number_format($totalListings), $listingGrowth, 'bi-building') ?>
                <?= dashboardStatCard('Active bookings', number_format($totalBookings), $bookingGrowth, 'bi-calendar-check') ?>
                <?= dashboardStatCard('Total earnings', '₦' . number_format($totalRevenue), $revenueGrowth, 'bi-wallet2') ?>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <!-- Action Queue -->
                <div class="col-span-12 lg:col-span-8 space-y-8">
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                            <h2 class="text-[18px] font-bold text-gray-900">Action queue</h2>
                            <button class="text-gray-400 hover:text-gray-900 transition-colors"><i class="bi bi-arrow-clockwise text-xl"></i></button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                                        <th class="py-4 px-8">Action entity</th>
                                        <th class="py-4 px-8">Description</th>
                                        <th class="py-4 px-8">Severity</th>
                                        <th class="py-4 px-8">Time</th>
                                        <th class="py-4 px-8 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-[14px]">
                                    <?php if (empty($actionQueue)): ?>
                                        <tr><td colspan="5" class="py-12 text-center text-gray-400 font-medium">No pending actions</td></tr>
                                    <?php else: ?>
                                        <?php foreach($actionQueue as $item): ?>
                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                <td class="py-5 px-8">
                                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($item['entity']) ?></div>
                                                </td>
                                                <td class="py-5 px-8 text-gray-500 font-medium"><?= htmlspecialchars($item['desc']) ?></td>
                                                <td class="py-5 px-8">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full <?= $item['severity'] === 'High' ? 'bg-orange-400' : 'bg-blue-400' ?>"></span>
                                                        <span class="font-bold <?= $item['severity'] === 'High' ? 'text-orange-500' : 'text-blue-500' ?> text-[13px] uppercase tracking-wider"><?= $item['severity'] ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-5 px-8 text-gray-400 font-medium"><?= date('h:i A', strtotime($item['time'])) ?></td>
                                                <td class="py-5 px-8 text-right">
                                                    <a href="<?= $item['action_link'] ?>" class="text-[#005a92] font-bold hover:underline"><?= $item['action_text'] ?></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recently Added Listings -->
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                            <h2 class="text-[18px] font-bold text-gray-900">Recently added listing</h2>
                            <button class="text-gray-400 hover:text-gray-900 transition-colors"><i class="bi bi-arrow-clockwise text-xl"></i></button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 bg-gray-50/30">
                                        <th class="py-4 px-8">Listing details</th>
                                        <th class="py-4 px-8">Location</th>
                                        <th class="py-4 px-8">Host</th>
                                        <th class="py-4 px-8">Status</th>
                                        <th class="py-4 px-8">Price</th>
                                        <th class="py-4 px-8 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-[14px]">
                                    <?php if (empty($recentListings)): ?>
                                        <tr><td colspan="6" class="py-12 text-center text-gray-400 font-medium">No recently added listings</td></tr>
                                    <?php else: ?>
                                        <?php foreach($recentListings as $p): 
                                            $statusClass = 'bg-yellow-50 text-yellow-600';
                                            if($p['status'] === 'published' || $p['status'] === 'active') $statusClass = 'bg-green-50 text-green-500';
                                            if($p['status'] === 'rejected' || $p['status'] === 'archived') $statusClass = 'bg-red-50 text-red-500';
                                        ?>
                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                <td class="py-5 px-8">
                                                    <div class="flex items-center gap-4">
                                                        <div class="h-10 w-10 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                                                            <img src="../api/placeholder_image.php" class="h-full w-full object-cover">
                                                        </div>
                                                        <div>
                                                            <div class="font-bold text-gray-900 truncate max-w-[150px]"><?= htmlspecialchars($p['name'] ?? 'Untitled') ?></div>
                                                            <div class="text-[11px] text-gray-300 font-medium uppercase tracking-tighter mt-0.5">ID: LST-<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-5 px-8 text-gray-500 font-medium whitespace-nowrap"><?= htmlspecialchars($p['city']) ?>, <?= htmlspecialchars($p['state']) ?></td>
                                                <td class="py-5 px-8 font-bold text-gray-900 whitespace-nowrap"><?= htmlspecialchars($p['first_name']) ?> <?= htmlspecialchars($p['last_name']) ?></td>
                                                <td class="py-5 px-8">
                                                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase <?= $statusClass ?>"><?= $p['status'] ?></span>
                                                </td>
                                                <td class="py-5 px-8 font-bold text-gray-900 whitespace-nowrap">₦<?= number_format($p['price']) ?>/m</td>
                                                <td class="py-5 px-8 text-right">
                                                    <a href="property_view.php?id=<?= $p['id'] ?>" class="p-2 text-gray-300 hover:text-gray-900 transition-colors"><i class="bi bi-eye-fill"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Activities -->
                <div class="col-span-12 lg:col-span-4 h-fit">
                    <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-8">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-[18px] font-bold text-gray-900">Activities</h2>
                        </div>
                        <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-8">
                            <button class="flex-1 py-2.5 text-[13px] font-bold rounded-lg bg-white shadow-sm text-gray-900">Today</button>
                            <button class="flex-1 py-2.5 text-[13px] font-bold text-gray-400">Yesterday</button>
                            <button class="flex-1 py-2.5 text-[13px] font-bold text-gray-400">This week</button>
                        </div>
                        <div class="space-y-6">
                            <?php if (empty($activities)): ?>
                                <div class="text-center text-gray-400 py-12 font-medium">No recent activities</div>
                            <?php else: ?>
                                <?php foreach($activities as $act): ?>
                                    <div class="flex gap-4 items-start p-5 rounded-[20px] border border-gray-50 bg-gray-50/30 group hover:bg-white hover:shadow-md transition-all">
                                        <div class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center shadow-sm shrink-0">
                                            <i class="bi <?= $act['type'] === 'property' ? 'bi-house' : 'bi-person' ?> text-gray-400 group-hover:text-primary transition-colors"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="font-bold text-[14px] text-gray-900 truncate"><?= htmlspecialchars($act['title']) ?></span>
                                                <span class="text-[11px] text-gray-400 font-medium"><?= date('h:i A', strtotime($act['time'])) ?></span>
                                            </div>
                                            <p class="text-[12px] text-gray-400 leading-tight line-clamp-2"><?= htmlspecialchars($act['desc']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php
    function dashboardStatCard($label, $val, $growth, $icon) {
        $growthColor = $growth >= 0 ? 'text-green-500' : 'text-red-500';
        $growthText = ($growth >= 0 ? '+' : '') . $growth . '% vs last month';
        return "
            <div class='bg-white p-8 rounded-[24px] border border-gray-100 shadow-sm relative overflow-hidden group'>
                <div class='flex items-center gap-3 mb-6'>
                    <div class='w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-gray-400'>
                         <i class='bi {$icon} text-[20px]'></i>
                    </div>
                    <span class='text-[15px] font-bold text-gray-800'>{$label}</span>
                </div>
                <div class='text-[36px] font-bold text-gray-900 mb-2'>{$val}</div>
                <div class='text-[13px] font-bold {$growthColor}'>{$growthText}</div>
            </div>
        ";
    }
    ?>

    <script>
        <?php if (isset($_SESSION['jwt_token'])): ?>
            localStorage.setItem('jwt_token', '<?= $_SESSION['jwt_token'] ?>');
        <?php endif; ?>
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>
