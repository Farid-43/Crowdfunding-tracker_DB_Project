-- =====================================================
-- Sample Data for Rewards and Related Tables
-- Date: October 25, 2025
-- =====================================================

USE CF_Tracker;

-- =====================================================
-- Insert Sample Rewards for Existing Campaigns
-- =====================================================

-- Rewards for Campaign 1
INSERT INTO Rewards (campaign_id, title, description, min_amount, max_backers, estimated_delivery, is_available) VALUES
(1, 'Thank You Card', 'A personalized thank you card from our team', 10.00, NULL, DATE_ADD(CURDATE(), INTERVAL 30 DAY), TRUE),
(1, 'Early Bird Special', 'Get the product at 20% off retail price + thank you card', 50.00, 100, DATE_ADD(CURDATE(), INTERVAL 60 DAY), TRUE),
(1, 'Standard Backer', 'One product unit + digital wallpapers + updates', 100.00, 500, DATE_ADD(CURDATE(), INTERVAL 90 DAY), TRUE),
(1, 'Super Backer', 'Two product units + exclusive merchandise + name in credits', 200.00, 200, DATE_ADD(CURDATE(), INTERVAL 90 DAY), TRUE),
(1, 'VIP Backer', 'Five units + meet the team + lifetime updates + all previous rewards', 500.00, 50, DATE_ADD(CURDATE(), INTERVAL 120 DAY), TRUE);

-- Rewards for Campaign 2
INSERT INTO Rewards (campaign_id, title, description, min_amount, max_backers, estimated_delivery, is_available) VALUES
(2, 'Digital Thank You', 'Digital certificate and email updates', 5.00, NULL, DATE_ADD(CURDATE(), INTERVAL 7 DAY), TRUE),
(2, 'Supporter Pack', 'Product sample + sticker pack + digital thank you', 25.00, 300, DATE_ADD(CURDATE(), INTERVAL 45 DAY), TRUE),
(2, 'Premium Pack', 'Full product + t-shirt + supporter pack rewards', 75.00, 150, DATE_ADD(CURDATE(), INTERVAL 60 DAY), TRUE),
(2, 'Deluxe Edition', 'Limited edition packaging + premium pack + poster', 150.00, 75, DATE_ADD(CURDATE(), INTERVAL 75 DAY), TRUE);

-- Rewards for Campaign 3
INSERT INTO Rewards (campaign_id, title, description, min_amount, max_backers, estimated_delivery, is_available) VALUES
(3, 'Contributor', 'Name listed on website + email updates', 15.00, NULL, DATE_ADD(CURDATE(), INTERVAL 14 DAY), TRUE),
(3, 'Supporter', 'Contributor rewards + exclusive newsletter + beta access', 40.00, 200, DATE_ADD(CURDATE(), INTERVAL 30 DAY), TRUE),
(3, 'Advocate', 'All previous rewards + branded merchandise + early access', 100.00, 100, DATE_ADD(CURDATE(), INTERVAL 45 DAY), TRUE);

-- Rewards for Campaign 4
INSERT INTO Rewards (campaign_id, title, description, min_amount, max_backers, estimated_delivery, is_available) VALUES
(4, 'Believer Badge', 'Digital badge + project updates', 20.00, NULL, DATE_ADD(CURDATE(), INTERVAL 10 DAY), TRUE),
(4, 'Core Supporter', 'Believer badge + physical thank you card + exclusive content', 60.00, 250, DATE_ADD(CURDATE(), INTERVAL 50 DAY), TRUE),
(4, 'Champion Tier', 'All previous + limited edition item + video call with team', 250.00, 30, DATE_ADD(CURDATE(), INTERVAL 80 DAY), TRUE);

-- Rewards for Campaign 5
INSERT INTO Rewards (campaign_id, title, description, min_amount, max_backers, estimated_delivery, is_available) VALUES
(5, 'Starter Reward', 'Digital download + thank you email', 8.00, NULL, DATE_ADD(CURDATE(), INTERVAL 5 DAY), TRUE),
(5, 'Basic Pack', 'Starter reward + physical copy + bookmark', 30.00, 400, DATE_ADD(CURDATE(), INTERVAL 40 DAY), TRUE),
(5, 'Collector Edition', 'Basic pack + signed edition + art print', 80.00, 150, DATE_ADD(CURDATE(), INTERVAL 55 DAY), TRUE),
(5, 'Ultimate Fan', 'All previous + exclusive merchandise + meet & greet', 200.00, 25, DATE_ADD(CURDATE(), INTERVAL 70 DAY), TRUE);

-- =====================================================
-- Populate Campaign_Category (Many-to-Many relationship)
-- Assign multiple categories to campaigns
-- =====================================================

-- First, ensure we have some categories
-- (Assuming categories 1-5 exist from sample_data.sql)

INSERT INTO Campaign_Category (campaign_id, category_id) VALUES
-- Campaign 1: Technology + Innovation
(1, 1),
(1, 2),

-- Campaign 2: Art + Design
(2, 3),
(2, 4),

-- Campaign 3: Technology + Education
(3, 1),
(3, 5),

-- Campaign 4: Social + Community
(4, 5),

-- Campaign 5: Art + Entertainment
(5, 3);

-- =====================================================
-- Sample Donor_Rewards (Donors claiming rewards)
-- Link existing donations to rewards
-- =====================================================

-- Assuming we have donations with IDs 1-10 from sample_data.sql
-- Let's have some donors claim rewards

-- Donor from donation 1 claims a reward
INSERT INTO Donor_Rewards (donor_id, reward_id, donation_id, fulfillment_status, notes)
SELECT donor_id, 1, donation_id, 'pending', 'First reward claimed!'
FROM Donations WHERE donation_id = 1 AND amount >= 10.00
LIMIT 1;

-- Donor from donation 2 claims a reward
INSERT INTO Donor_Rewards (donor_id, reward_id, donation_id, fulfillment_status, notes)
SELECT donor_id, 3, donation_id, 'processing', 'Standard backer tier'
FROM Donations WHERE donation_id = 2 AND amount >= 100.00
LIMIT 1;

-- Donor from donation 3 claims a reward
INSERT INTO Donor_Rewards (donor_id, reward_id, donation_id, fulfillment_status, notes)
SELECT donor_id, 6, donation_id, 'pending', 'Digital thank you'
FROM Donations WHERE donation_id = 3 AND amount >= 5.00
LIMIT 1;

-- Donor from donation 4 claims a reward
INSERT INTO Donor_Rewards (donor_id, reward_id, donation_id, fulfillment_status, notes)
SELECT donor_id, 10, donation_id, 'shipped', 'Contributor tier - shipped!'
FROM Donations WHERE donation_id = 4 AND amount >= 15.00
LIMIT 1;

-- Donor from donation 5 claims a reward
INSERT INTO Donor_Rewards (donor_id, reward_id, donation_id, fulfillment_status, notes)
SELECT donor_id, 15, donation_id, 'pending', 'Starter reward claimed'
FROM Donations WHERE donation_id = 5 AND amount >= 8.00
LIMIT 1;

-- =====================================================
-- Verification Queries
-- =====================================================

-- Show all rewards
SELECT 'All Rewards:' AS Info;
SELECT 
    r.reward_id,
    c.campaign_title,
    r.title AS reward_title,
    r.min_amount,
    r.max_backers,
    r.current_backers,
    r.is_available,
    r.estimated_delivery
FROM Rewards r
JOIN Campaigns c ON r.campaign_id = c.campaign_id
ORDER BY r.campaign_id, r.min_amount;

-- Show campaign categories (many-to-many)
SELECT 'Campaign Categories (Many-to-Many):' AS Info;
SELECT 
    c.campaign_id,
    c.campaign_title,
    GROUP_CONCAT(cat.category_name SEPARATOR ', ') AS categories
FROM Campaigns c
LEFT JOIN Campaign_Category cc ON c.campaign_id = cc.campaign_id
LEFT JOIN Categories cat ON cc.category_id = cat.category_id
GROUP BY c.campaign_id, c.campaign_title
ORDER BY c.campaign_id;

-- Show donor rewards claimed
SELECT 'Donor Rewards Claimed:' AS Info;
SELECT 
    u.username AS donor,
    r.title AS reward_title,
    c.campaign_title,
    d.amount AS donation_amount,
    dr.fulfillment_status,
    dr.claimed_at
FROM Donor_Rewards dr
JOIN Users u ON dr.donor_id = u.user_id
JOIN Rewards r ON dr.reward_id = r.reward_id
JOIN Campaigns c ON r.campaign_id = c.campaign_id
JOIN Donations d ON dr.donation_id = d.donation_id
ORDER BY dr.claimed_at DESC;

-- Show reward availability stats
SELECT 'Reward Availability Stats:' AS Info;
SELECT 
    c.campaign_title,
    COUNT(r.reward_id) AS total_rewards,
    SUM(CASE WHEN r.is_available = TRUE THEN 1 ELSE 0 END) AS available_rewards,
    SUM(r.current_backers) AS total_backers,
    SUM(CASE WHEN r.max_backers IS NOT NULL THEN r.max_backers ELSE 0 END) AS total_max_capacity
FROM Campaigns c
LEFT JOIN Rewards r ON c.campaign_id = r.campaign_id
GROUP BY c.campaign_id, c.campaign_title
ORDER BY total_rewards DESC;

SELECT 'Sample data for Rewards inserted successfully!' AS Status;
