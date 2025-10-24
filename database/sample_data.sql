-- =====================================================
-- Sample Data for CF_Tracker Database
-- Provides test data to demonstrate all SQL features
-- =====================================================

USE CF_Tracker;

-- Disable foreign key checks temporarily for data insertion
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Insert Categories
-- =====================================================

INSERT INTO Categories (category_name, description, icon) VALUES
('Technology', 'Tech innovations, apps, and gadgets', 'fa-laptop'),
('Arts & Crafts', 'Creative projects and artwork', 'fa-palette'),
('Music', 'Music albums, concerts, and instruments', 'fa-music'),
('Film & Video', 'Movies, documentaries, and video projects', 'fa-video'),
('Games', 'Board games, video games, and gaming content', 'fa-gamepad'),
('Food & Beverage', 'Restaurants, food products, and culinary projects', 'fa-utensils'),
('Fashion', 'Clothing lines and fashion accessories', 'fa-tshirt'),
('Publishing', 'Books, magazines, and written content', 'fa-book'),
('Education', 'Educational programs and learning tools', 'fa-graduation-cap'),
('Community', 'Local community projects and social causes', 'fa-hands-helping');

-- =====================================================
-- Insert Users
-- =====================================================

INSERT INTO Users (username, email, password_hash, full_name, user_role, phone, bio, account_balance, is_active, email_verified) VALUES
('admin_user', 'admin@cftracker.com', '$2y$10$YourHashedPasswordHere', 'Admin User', 'admin', '555-0001', 'Platform administrator', 0.00, TRUE, TRUE),
('john_doe', 'john@example.com', '$2y$10$YourHashedPasswordHere', 'John Doe', 'campaigner', '555-0102', 'Tech entrepreneur and innovator', 0.00, TRUE, TRUE),
('jane_smith', 'jane@example.com', '$2y$10$YourHashedPasswordHere', 'Jane Smith', 'donor', '555-0103', 'Passionate supporter of creative projects', 500.00, TRUE, TRUE),
('mike_wilson', 'mike@example.com', '$2y$10$YourHashedPasswordHere', 'Mike Wilson', 'campaigner', '555-0104', 'Documentary filmmaker', 0.00, TRUE, TRUE),
('sarah_johnson', 'sarah@example.com', '$2y$10$YourHashedPasswordHere', 'Sarah Johnson', 'donor', '555-0105', 'Art enthusiast and collector', 1000.00, TRUE, TRUE),
('david_brown', 'david@example.com', '$2y$10$YourHashedPasswordHere', 'David Brown', 'campaigner', '555-0106', 'Game developer and designer', 0.00, TRUE, TRUE),
('emily_davis', 'emily@example.com', '$2y$10$YourHashedPasswordHere', 'Emily Davis', 'donor', '555-0107', 'Supporting education initiatives', 750.00, TRUE, TRUE),
('robert_taylor', 'robert@example.com', '$2y$10$YourHashedPasswordHere', 'Robert Taylor', 'campaigner', '555-0108', 'Chef and food blogger', 0.00, TRUE, TRUE),
('lisa_anderson', 'lisa@example.com', '$2y$10$YourHashedPasswordHere', 'Lisa Anderson', 'donor', '555-0109', 'Music lover and concert goer', 300.00, TRUE, TRUE),
('james_martinez', 'james@example.com', '$2y$10$YourHashedPasswordHere', 'James Martinez', 'campaigner', '555-0110', 'Author and publisher', 0.00, TRUE, TRUE),
('maria_garcia', 'maria@example.com', '$2y$10$YourHashedPasswordHere', 'Maria Garcia', 'donor', '555-0111', 'Community activist', 600.00, TRUE, TRUE),
('william_lee', 'william@example.com', '$2y$10$YourHashedPasswordHere', 'William Lee', 'donor', '555-0112', 'Tech industry professional', 2000.00, TRUE, TRUE),
('jennifer_white', 'jennifer@example.com', '$2y$10$YourHashedPasswordHere', 'Jennifer White', 'campaigner', '555-0113', 'Fashion designer', 0.00, TRUE, TRUE),
('thomas_harris', 'thomas@example.com', '$2y$10$YourHashedPasswordHere', 'Thomas Harris', 'donor', '555-0114', 'Philanthropist', 5000.00, TRUE, TRUE),
('jessica_clark', 'jessica@example.com', '$2y$10$YourHashedPasswordHere', 'Jessica Clark', 'donor', '555-0115', 'Education advocate', 400.00, TRUE, TRUE);

-- =====================================================
-- Insert Campaigns
-- =====================================================

INSERT INTO Campaigns (campaign_title, description, category, goal_amount, current_amount, creator_id, category_id, start_date, end_date, status, featured, image_url) VALUES
-- Active Campaigns
('Smart Home Hub 2.0', 'An AI-powered smart home controller that learns your preferences and automates your home.', 'Technology', 50000.00, 35000.00, 2, 1, '2025-01-01', '2025-12-31', 'active', TRUE, 'smart-home.jpg'),
('Ocean Documentary Series', 'A 6-part documentary exploring the hidden world beneath our oceans.', 'Film & Video', 75000.00, 45000.00, 4, 4, '2025-02-01', '2025-11-30', 'active', TRUE, 'ocean-doc.jpg'),
('Indie Game: Pixel Quest', 'A nostalgic 16-bit adventure game with modern gameplay mechanics.', 'Games', 30000.00, 28500.00, 6, 5, '2025-01-15', '2025-10-15', 'active', FALSE, 'pixel-quest.jpg'),
('Community Garden Project', 'Building a sustainable urban garden for the local community.', 'Community', 15000.00, 12000.00, 2, 10, '2025-03-01', '2025-09-30', 'active', TRUE, 'garden.jpg'),
('Jazz Album: Midnight Blue', 'Recording and producing my debut jazz album.', 'Music', 20000.00, 8500.00, 2, 3, '2025-02-15', '2025-08-15', 'active', FALSE, 'jazz-album.jpg'),
('Artisan Coffee Roastery', 'Opening a small-batch coffee roasting business in downtown.', 'Food & Beverage', 40000.00, 15000.00, 8, 6, '2025-01-20', '2025-12-20', 'active', FALSE, 'coffee.jpg'),
('Children Education App', 'Interactive learning app for elementary school children.', 'Education', 60000.00, 42000.00, 2, 9, '2025-01-10', '2025-11-10', 'active', TRUE, 'edu-app.jpg'),
('Sustainable Fashion Line', 'Eco-friendly clothing made from recycled materials.', 'Fashion', 25000.00, 10000.00, 13, 7, '2025-03-15', '2025-10-31', 'active', FALSE, 'eco-fashion.jpg'),

-- Completed Campaigns
('Photography Book: Urban Life', 'A collection of street photography from around the world.', 'Publishing', 12000.00, 15000.00, 10, 8, '2024-06-01', '2024-12-31', 'completed', FALSE, 'photo-book.jpg'),
('Vintage Arcade Restoration', 'Restoring classic arcade machines for a retro gaming cafe.', 'Games', 35000.00, 38000.00, 6, 5, '2024-05-01', '2024-11-30', 'completed', FALSE, 'arcade.jpg'),

-- Draft Campaigns
('VR Meditation Experience', 'Virtual reality guided meditation and mindfulness app.', 'Technology', 45000.00, 0.00, 2, 1, '2025-06-01', '2025-12-31', 'draft', FALSE, 'vr-med.jpg'),
('Cookbook: Farm to Table', 'Seasonal recipes using locally sourced ingredients.', 'Food & Beverage', 18000.00, 0.00, 8, 6, '2025-07-01', '2026-01-31', 'draft', FALSE, 'cookbook.jpg');

-- =====================================================
-- Insert Donations
-- =====================================================

INSERT INTO Donations (campaign_id, donor_id, amount, payment_method, message, is_anonymous, status) VALUES
-- Donations for Smart Home Hub
(1, 3, 500.00, 'credit_card', 'Excited to see this come to life!', FALSE, 'completed'),
(1, 5, 1000.00, 'paypal', 'Great innovation!', FALSE, 'completed'),
(1, 7, 250.00, 'credit_card', '', FALSE, 'completed'),
(1, 12, 2000.00, 'bank_transfer', 'Looking forward to using this!', FALSE, 'completed'),
(1, 14, 5000.00, 'credit_card', '', TRUE, 'completed'),

-- Donations for Ocean Documentary
(2, 3, 750.00, 'paypal', 'Love ocean documentaries!', FALSE, 'completed'),
(2, 5, 1500.00, 'credit_card', 'Important work!', FALSE, 'completed'),
(2, 9, 200.00, 'paypal', '', FALSE, 'completed'),
(2, 11, 500.00, 'credit_card', 'Cant wait to watch!', FALSE, 'completed'),
(2, 12, 3000.00, 'bank_transfer', '', FALSE, 'completed'),
(2, 14, 7500.00, 'credit_card', 'Supporting conservation!', FALSE, 'completed'),

-- Donations for Pixel Quest
(3, 3, 100.00, 'paypal', 'Retro games rock!', FALSE, 'completed'),
(3, 5, 500.00, 'credit_card', '', FALSE, 'completed'),
(3, 7, 150.00, 'paypal', 'Nostalgic!', FALSE, 'completed'),
(3, 9, 300.00, 'credit_card', 'Good luck!', FALSE, 'completed'),
(3, 12, 1000.00, 'paypal', '', FALSE, 'completed'),

-- Donations for Community Garden
(4, 3, 250.00, 'credit_card', 'Great community initiative!', FALSE, 'completed'),
(4, 5, 500.00, 'paypal', '', FALSE, 'completed'),
(4, 7, 300.00, 'credit_card', 'Supporting local!', FALSE, 'completed'),
(4, 11, 600.00, 'bank_transfer', '', FALSE, 'completed'),
(4, 15, 400.00, 'credit_card', 'Love this idea!', FALSE, 'completed'),

-- Donations for Jazz Album
(5, 3, 100.00, 'paypal', '', FALSE, 'completed'),
(5, 9, 200.00, 'credit_card', 'Jazz fan here!', FALSE, 'completed'),
(5, 12, 500.00, 'paypal', '', TRUE, 'completed'),

-- Donations for Coffee Roastery
(6, 3, 200.00, 'credit_card', 'Coffee lover!', FALSE, 'completed'),
(6, 5, 500.00, 'paypal', '', FALSE, 'completed'),
(6, 12, 1000.00, 'bank_transfer', 'Good luck with the business!', FALSE, 'completed'),

-- Donations for Education App
(7, 3, 500.00, 'paypal', 'Education is important!', FALSE, 'completed'),
(7, 5, 1000.00, 'credit_card', '', FALSE, 'completed'),
(7, 7, 750.00, 'paypal', 'Supporting education!', FALSE, 'completed'),
(7, 14, 10000.00, 'bank_transfer', 'Investing in our future!', FALSE, 'completed'),
(7, 15, 400.00, 'credit_card', '', FALSE, 'completed'),

-- Donations for Fashion Line
(8, 3, 150.00, 'paypal', 'Sustainability matters!', FALSE, 'completed'),
(8, 5, 250.00, 'credit_card', '', FALSE, 'completed'),
(8, 11, 600.00, 'paypal', 'Great concept!', FALSE, 'completed'),

-- Donations for completed campaigns
(9, 3, 100.00, 'credit_card', '', FALSE, 'completed'),
(9, 5, 500.00, 'paypal', 'Beautiful photos!', FALSE, 'completed'),
(9, 7, 200.00, 'credit_card', '', FALSE, 'completed'),
(9, 12, 1000.00, 'bank_transfer', '', FALSE, 'completed'),

(10, 3, 500.00, 'paypal', 'Retro gaming!', FALSE, 'completed'),
(10, 5, 1000.00, 'credit_card', '', FALSE, 'completed'),
(10, 12, 2000.00, 'bank_transfer', '', FALSE, 'completed'),
(10, 14, 5000.00, 'credit_card', '', TRUE, 'completed');

-- =====================================================
-- Insert Comments
-- =====================================================

INSERT INTO Comments (campaign_id, user_id, parent_comment_id, comment_text) VALUES
(1, 3, NULL, 'This looks amazing! When do you expect to ship?'),
(1, 2, 1, 'Thanks! We are targeting Q4 2025 for first shipments.'),
(1, 5, NULL, 'Will it work with existing smart home devices?'),
(1, 2, 3, 'Yes! It is compatible with most major brands.'),
(2, 9, NULL, 'Which oceans will you be covering?'),
(2, 4, 5, 'We will be filming in all five major oceans!'),
(3, 7, NULL, 'What platforms will this be available on?'),
(3, 6, 7, 'PC, Mac, and Nintendo Switch at launch!'),
(4, 11, NULL, 'How can I volunteer to help with the garden?'),
(7, 15, NULL, 'What age group is this app designed for?'),
(7, 2, 10, 'Primarily ages 6-12, but fun for all ages!');

-- =====================================================
-- Insert Campaign Updates
-- =====================================================

INSERT INTO Campaign_Updates (campaign_id, title, content) VALUES
(1, 'Prototype Complete!', 'We have completed our working prototype and tested it with beta users. The feedback has been amazing!'),
(1, '70% Funded Milestone', 'Thank you all for your incredible support! We have reached 70% of our funding goal!'),
(2, 'First Dive Completed', 'We have completed our first dive in the Pacific Ocean. The footage is stunning!'),
(2, 'Meet the Team', 'Get to know our award-winning crew of marine biologists and cinematographers.'),
(3, 'Demo Available!', 'A playable demo is now available for all backers! Check your email for the download link.'),
(4, 'Location Secured', 'We have secured the perfect location for our community garden! Construction begins next month.'),
(7, 'Beta Testing Begins', 'We are now in beta testing phase with select schools. The results are very promising!');

-- =====================================================
-- Insert User Followers
-- =====================================================

INSERT INTO User_Followers (follower_id, following_id) VALUES
(3, 2),  -- Jane follows John
(5, 2),  -- Sarah follows John
(7, 2),  -- Emily follows John
(3, 4),  -- Jane follows Mike
(5, 4),  -- Sarah follows Mike
(9, 4),  -- Lisa follows Mike
(3, 6),  -- Jane follows David
(7, 6),  -- Emily follows David
(3, 8),  -- Jane follows Robert
(5, 10), -- Sarah follows James
(12, 2), -- William follows John
(14, 2), -- Thomas follows John
(11, 4), -- Maria follows Mike
(15, 2); -- Jessica follows John

-- =====================================================
-- Insert Campaign Favorites
-- =====================================================

INSERT INTO Campaign_Favorites (user_id, campaign_id) VALUES
(3, 1),
(3, 2),
(3, 3),
(5, 1),
(5, 2),
(5, 7),
(7, 3),
(7, 4),
(7, 7),
(9, 2),
(9, 5),
(11, 4),
(12, 1),
(12, 7),
(14, 2),
(14, 7),
(15, 7);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Update campaign current_amount to match donations
-- =====================================================

UPDATE Campaigns c
SET current_amount = (
    SELECT COALESCE(SUM(d.amount), 0)
    FROM Donations d
    WHERE d.campaign_id = c.campaign_id AND d.status = 'completed'
);

-- =====================================================
-- Verify Data
-- =====================================================

SELECT 'Data Import Complete!' as Status,
       (SELECT COUNT(*) FROM Users) as Total_Users,
       (SELECT COUNT(*) FROM Categories) as Total_Categories,
       (SELECT COUNT(*) FROM Campaigns) as Total_Campaigns,
       (SELECT COUNT(*) FROM Donations) as Total_Donations,
       (SELECT COUNT(*) FROM Comments) as Total_Comments,
       (SELECT COALESCE(SUM(amount), 0) FROM Donations WHERE status = 'completed') as Total_Funds_Raised;
