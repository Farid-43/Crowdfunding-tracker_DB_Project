<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'CF Tracker - SQL Learning Platform'; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom SQL Styles -->
    <link rel="stylesheet" href="/Crowdfunding-tracker_DB_Project/assets/css/sql-styles.css">
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#8b5cf6',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Additional custom styles */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        
        .nav-link {
            position: relative;
            transition: color 0.2s;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #3b82f6;
            transition: width 0.3s;
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        
        .badge {
            font-size: 0.7em;
            padding: 2px 8px;
            border-radius: 12px;
            background: #ef4444;
            color: white;
            margin-left: 6px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <a href="/Crowdfunding-tracker_DB_Project/index.php" class="flex items-center">
                        <i class="fas fa-database text-3xl text-blue-600 mr-3"></i>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">CF Tracker</h1>
                            <p class="text-xs text-gray-500">SQL Learning Platform</p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/Crowdfunding-tracker_DB_Project/index.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="/Crowdfunding-tracker_DB_Project/campaigns.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'campaigns' ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn mr-2"></i>Campaigns
                    </a>
                    <a href="/Crowdfunding-tracker_DB_Project/donations.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'donations' ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd mr-2"></i>Donations
                    </a>
                    <a href="/Crowdfunding-tracker_DB_Project/rewards.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'rewards' ? 'active' : ''; ?>">
                        <i class="fas fa-gift mr-2"></i>Rewards
                        
                    </a>
                    <a href="/Crowdfunding-tracker_DB_Project/analytics.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'analytics' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line mr-2"></i>Analytics
                    </a>
                    <a href="/Crowdfunding-tracker_DB_Project/users.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users mr-2"></i>Users
                    </a>
                    <a href="/Crowdfunding-tracker_DB_Project/sql_features.php" 
                       class="nav-link text-gray-700 hover:text-blue-600 font-medium <?php echo ($current_page ?? '') === 'sql_features' ? 'active' : ''; ?>">
                        <i class="fas fa-code mr-2"></i>SQL Features
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-3 space-y-2">
                <a href="/Crowdfunding-tracker_DB_Project/index.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="/Crowdfunding-tracker_DB_Project/campaigns.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-bullhorn mr-2"></i>Campaigns
                </a>
                <a href="/Crowdfunding-tracker_DB_Project/donations.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-hand-holding-usd mr-2"></i>Donations
                </a>
                <a href="/Crowdfunding-tracker_DB_Project/rewards.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-gift mr-2"></i>Rewards <span class="badge">NEW</span>
                </a>
                <a href="/Crowdfunding-tracker_DB_Project/analytics.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-chart-line mr-2"></i>Analytics
                </a>
                <a href="/Crowdfunding-tracker_DB_Project/users.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-users mr-2"></i>Users
                </a>
                <a href="/Crowdfunding-tracker_DB_Project/sql_features.php" 
                   class="block py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded">
                    <i class="fas fa-code mr-2"></i>SQL Features
                </a>
            </div>
        </div>
    </nav>

    <!-- SQL Learning Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center text-center">
                <i class="fas fa-graduation-cap mr-3 text-2xl"></i>
                <p class="text-sm md:text-base">
                    <strong>Educational Platform:</strong> Hover over any button/link to see the SQL query it executes! 
                    <span class="hidden md:inline">Click to view detailed explanations.</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
