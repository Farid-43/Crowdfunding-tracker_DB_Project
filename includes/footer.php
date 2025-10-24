    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div>
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-database mr-2"></i>CF Tracker
                    </h3>
                    <p class="text-gray-400 text-sm">
                        An educational SQL demonstration platform showcasing every feature from Oracle SQL curriculum using a crowdfunding tracker application.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/Crowdfunding-tracker_DB_Project/index.php" class="text-gray-400 hover:text-white"><i class="fas fa-home mr-2"></i>Dashboard</a></li>
                        <li><a href="/Crowdfunding-tracker_DB_Project/campaigns.php" class="text-gray-400 hover:text-white"><i class="fas fa-bullhorn mr-2"></i>Campaigns</a></li>
                        <li><a href="/Crowdfunding-tracker_DB_Project/donations.php" class="text-gray-400 hover:text-white"><i class="fas fa-hand-holding-usd mr-2"></i>Donations</a></li>
                        <li><a href="/Crowdfunding-tracker_DB_Project/analytics.php" class="text-gray-400 hover:text-white"><i class="fas fa-chart-line mr-2"></i>Analytics</a></li>
                    </ul>
                </div>

                <!-- SQL Features -->
                <div>
                    <h3 class="text-lg font-bold mb-4">SQL Features</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><i class="fas fa-check mr-2 text-green-400"></i>JOINs (All Types)</li>
                        <li><i class="fas fa-check mr-2 text-green-400"></i>Aggregation & GROUP BY</li>
                        <li><i class="fas fa-check mr-2 text-green-400"></i>Stored Procedures</li>
                        <li><i class="fas fa-check mr-2 text-green-400"></i>Triggers & Functions</li>
                        <li><i class="fas fa-check mr-2 text-green-400"></i>CTEs & Window Functions</li>
                        <li><a href="/Crowdfunding-tracker_DB_Project/sql_features.php" class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-arrow-right mr-2"></i>View All Features
                        </a></li>
                    </ul>
                </div>

                <!-- Stats -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Platform Stats</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">SQL Features:</span>
                            <span class="font-bold text-blue-400">40+</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Tables:</span>
                            <span class="font-bold text-blue-400">12</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Views:</span>
                            <span class="font-bold text-blue-400">4</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Procedures:</span>
                            <span class="font-bold text-blue-400">5</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Functions:</span>
                            <span class="font-bold text-blue-400">4</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Triggers:</span>
                            <span class="font-bold text-blue-400">5</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>
                    <i class="fas fa-code mr-2"></i>
                    Built with PHP, MySQL, Tailwind CSS | 
                    <i class="fas fa-graduation-cap mx-2"></i>
                    Educational SQL Demonstration Platform | 
                    <i class="fas fa-calendar-alt mx-2"></i>
                    2025
                </p>
                <p class="mt-2 text-xs">
                    💡 Tip: Hover over any interactive element to see the SQL query it executes!
                </p>
            </div>
        </div>
    </footer>

    <!-- SQL Tooltip Script -->
    <script src="/Crowdfunding-tracker_DB_Project/assets/js/sql-tooltip.js"></script>
    
    <!-- Mobile Menu Toggle -->
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileBtn = document.getElementById('mobile-menu-btn');
            
            if (!mobileMenu.contains(event.target) && !mobileBtn.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
    
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
</body>
</html>
