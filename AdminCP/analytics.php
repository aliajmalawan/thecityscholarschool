<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/analytics_helper.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get date range filter (default: all time)
$date_range = isset($_GET['range']) ? $_GET['range'] : 'all';

// Fetch analytics statistics
$stats = [
    'total_views' => getTotalPageViews($conn, $date_range),
    'unique_visitors' => getUniqueVisitors($conn, $date_range),
    'active_users' => getActiveUsers($conn),
    'bounce_rate' => getBounceRate($conn, $date_range)
];

// Get popular pages
$popular_pages = getPopularPages($conn, 10, $date_range);

// Get device statistics
$device_stats = getDeviceStats($conn, $date_range);

// Get browser statistics
$browser_stats = getBrowserStats($conn, $date_range);

// Get traffic sources
$traffic_sources = getTrafficSources($conn, 10, $date_range);

// Get hourly traffic for today
$hourly_traffic = getHourlyTraffic($conn);
$hourly_data = array_fill(0, 24, 0);
while ($row = mysqli_fetch_assoc($hourly_traffic)) {
    $hourly_data[$row['hour']] = $row['visits'];
}

// Get daily traffic for last 30 days
$daily_traffic = getDailyTraffic($conn, 30);
$visits_by_day = [];
while ($row = mysqli_fetch_assoc($daily_traffic)) {
    $visits_by_day[$row['visit_date']] = (int) $row['visits'];
}
// One point per day, so quiet days show as 0 instead of being skipped
$daily_labels = [];
$daily_data = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $daily_labels[] = date('M d', strtotime($day));
    $daily_data[] = $visits_by_day[$day] ?? 0;
}

// Helper function to convert page URL to friendly name
function getPageName($url) {
    // Remove leading slash
    $url = ltrim($url, '/');

    // Map of URLs to friendly names
    $page_names = [
        'index.php' => 'Home',
        'about.php' => 'About Us',
        'courses.php' => 'Courses',
        'admission.php' => 'Admission',
        'faculty.php' => 'Faculty',
        'events.php' => 'Events & News',
        'examination.php' => 'Examination',
        'gallery.php' => 'Gallery',
        'alumni.php' => 'Alumni',
        'downloads.php' => 'Downloads',
        'contact.php' => 'Contact Us'
    ];

    // Check if URL matches any known page
    foreach ($page_names as $page => $name) {
        if (strpos($url, $page) !== false) {
            return $name;
        }
    }

    // If not found, return cleaned URL (just filename)
    $parts = explode('/', $url);
    $filename = end($parts);
    return ucfirst(str_replace(['.php', '_', '-'], ['', ' ', ' '], $filename));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Website Analytics - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .welcome-text h1 {
            color: #78864F;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: #666;
            font-size: 14px;
        }

        /* Date Filter */
        .date-filter {
            display: flex;
            gap: 10px;
            background: #f5f7fa;
            padding: 5px;
            border-radius: 10px;
        }

        .date-filter a {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #666;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .date-filter a:hover {
            background: white;
            color: #78864F;
        }

        .date-filter a.active {
            background: #78864F;
            color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-card-icon.blue { background: rgba(120, 134, 79, 0.1); color: #78864F; }
        .stat-card-icon.green { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .stat-card-icon.orange { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
        .stat-card-icon.red { background: rgba(244, 67, 54, 0.1); color: #f44336; }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stat-card .value {
            color: #78864F;
            font-size: 32px;
            font-weight: bold;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .chart-container h3 {
            color: #78864F;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        /* Charts Row Grid */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .charts-row .chart-container {
            margin-bottom: 0;
        }

        /* Two Column Grid */
        .two-column-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 400px), 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #666;
            font-size: 13px;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }

        .data-table tr:hover {
            background: #f9f9f9;
        }

        .page-name {
            color: #78864F;
            font-weight: 500;
        }

        .views-badge {
            background: #78864F;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: #78864F;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .device-item, .source-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .device-item:last-child, .source-item:last-child {
            border-bottom: none;
        }

        .device-name, .source-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            font-weight: 500;
        }

        .device-count, .source-count {
            color: #78864F;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .charts-row {
                grid-template-columns: 1fr;
            }

            .two-column-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-card .value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <?php $active_page = 'analytics.php'; include 'includes/sidebar.php'; ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="welcome-text">
                    <h1><i class="fas fa-chart-line"></i> Website Analytics</h1>
                    <p>Monitor your website traffic and visitor behavior</p>
                </div>
            </div>
            <div class="date-filter">
                <a href="?range=today" class="<?php echo $date_range == 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?range=week" class="<?php echo $date_range == 'week' ? 'active' : ''; ?>">7 Days</a>
                <a href="?range=month" class="<?php echo $date_range == 'month' ? 'active' : ''; ?>">30 Days</a>
                <a href="?range=all" class="<?php echo $date_range == 'all' ? 'active' : ''; ?>">All Time</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3>Total Page Views</h3>
                        <div class="value"><?php echo number_format($stats['total_views']); ?></div>
                    </div>
                    <div class="stat-card-icon blue">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3>Unique Visitors</h3>
                        <div class="value"><?php echo number_format($stats['unique_visitors']); ?></div>
                    </div>
                    <div class="stat-card-icon green">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3>Active Users (Now)</h3>
                        <div class="value"><?php echo number_format($stats['active_users']); ?></div>
                    </div>
                    <div class="stat-card-icon orange">
                        <i class="fas fa-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3>Bounce Rate</h3>
                        <div class="value"><?php echo $stats['bounce_rate']; ?>%</div>
                    </div>
                    <div class="stat-card-icon red">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row: Traffic Charts -->
        <div class="charts-row">
            <div class="chart-container">
                <h3><i class="fas fa-chart-area"></i> Daily Traffic (Last 30 Days)</h3>
                <div class="chart-wrapper">
                    <canvas id="dailyTrafficChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3><i class="fas fa-clock"></i> Hourly Traffic (Today)</h3>
                <div class="chart-wrapper">
                    <canvas id="hourlyTrafficChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Two Column Grid -->
        <div class="two-column-grid">
            <!-- Popular Pages -->
            <div class="chart-container">
                <h3><i class="fas fa-fire"></i> Most Popular Pages</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Page Name</th>
                            <th style="text-align: right;">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($popular_pages) > 0) {
                            $max_views = 0;
                            $pages_array = [];
                            while ($page = mysqli_fetch_assoc($popular_pages)) {
                                $pages_array[] = $page;
                                if ($page['views'] > $max_views) $max_views = $page['views'];
                            }

                            foreach ($pages_array as $page) {
                                $percentage = ($page['views'] / $max_views) * 100;
                                $page_name = getPageName($page['page_url']);
                                echo "<tr>";
                                echo "<td><span class='page-name'>" . htmlspecialchars($page_name) . "</span>";
                                echo "<div class='progress-bar'><div class='progress-fill' style='width: {$percentage}%'></div></div></td>";
                                echo "<td style='text-align: right;'><span class='views-badge'>" . number_format($page['views']) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='2' style='text-align: center; color: #999;'>No data available</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Device Statistics -->
            <div class="chart-container">
                <h3><i class="fas fa-mobile-alt"></i> Device Types</h3>
                <div class="chart-wrapper">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Two Column Grid -->
        <div class="two-column-grid">
            <!-- Browser Statistics -->
            <div class="chart-container">
                <h3><i class="fas fa-browser"></i> Top Browsers</h3>
                <?php
                $browser_data = [];
                while ($browser = mysqli_fetch_assoc($browser_stats)) {
                    $browser_data[] = $browser;
                }
                ?>
                <?php if (count($browser_data) > 0): ?>
                    <?php foreach ($browser_data as $browser): ?>
                        <div class="device-item">
                            <div class="device-name">
                                <i class="fas fa-globe"></i>
                                <?php echo htmlspecialchars($browser['browser']); ?>
                            </div>
                            <div class="device-count"><?php echo number_format($browser['count']); ?> visits</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No data available</p>
                <?php endif; ?>
            </div>

            <!-- Traffic Sources -->
            <div class="chart-container">
                <h3><i class="fas fa-share-alt"></i> Traffic Sources</h3>
                <?php
                $sources_data = [];
                while ($source = mysqli_fetch_assoc($traffic_sources)) {
                    $sources_data[] = $source;
                }
                ?>
                <?php if (count($sources_data) > 0): ?>
                    <?php foreach ($sources_data as $source): ?>
                        <div class="source-item">
                            <div class="source-name">
                                <i class="fas fa-arrow-right"></i>
                                <?php echo htmlspecialchars($source['source']); ?>
                            </div>
                            <div class="source-count"><?php echo number_format($source['visits']); ?> visits</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script>
        // Daily Traffic Chart
        const dailyCtx = document.getElementById('dailyTrafficChart').getContext('2d');
        const dailyTrafficChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($daily_labels); ?>,
                datasets: [{
                    label: 'Daily Visits',
                    data: <?php echo json_encode($daily_data); ?>,
                    backgroundColor: 'rgba(120, 134, 79, 0.1)',
                    borderColor: '#78864F',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Hourly Traffic Chart
        const hourlyCtx = document.getElementById('hourlyTrafficChart').getContext('2d');
        const hourlyTrafficChart = new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: ['12am', '1am', '2am', '3am', '4am', '5am', '6am', '7am', '8am', '9am', '10am', '11am',
                         '12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm', '7pm', '8pm', '9pm', '10pm', '11pm'],
                datasets: [{
                    label: 'Hourly Visits',
                    data: <?php echo json_encode($hourly_data); ?>,
                    backgroundColor: '#78864F',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Device Chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        <?php
        $device_labels = [];
        $device_data = [];
        $device_colors = [
            'Mobile' => '#4CAF50',
            'Desktop' => '#78864F',
            'Tablet' => '#FF9800',
            'Unknown' => '#999999'
        ];
        $chart_colors = [];

        mysqli_data_seek($device_stats, 0);
        while ($device = mysqli_fetch_assoc($device_stats)) {
            $device_labels[] = $device['device_type'];
            $device_data[] = $device['count'];
            $chart_colors[] = isset($device_colors[$device['device_type']]) ? $device_colors[$device['device_type']] : '#999999';
        }
        ?>
        const deviceChart = new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($device_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($device_data); ?>,
                    backgroundColor: <?php echo json_encode($chart_colors); ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
