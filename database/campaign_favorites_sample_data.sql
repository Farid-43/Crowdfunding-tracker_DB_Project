-- Sample Data for Campaign_Favorites Table
-- Demonstrates Many-to-Many relationship between Users and Campaigns

-- Insert sample favorites (user_id, campaign_id)
-- Assuming you have existing users and campaigns from sample_data.sql

-- User 1 favorites multiple campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(1, 1, '2024-01-15 10:30:00'),
(1, 3, '2024-01-16 14:20:00'),
(1, 5, '2024-01-17 09:15:00'),
(1, 7, '2024-01-18 16:45:00');

-- User 2 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(2, 1, '2024-01-15 11:00:00'),
(2, 2, '2024-01-16 13:30:00'),
(2, 5, '2024-01-17 15:20:00');

-- User 3 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(3, 1, '2024-01-16 08:45:00'),
(3, 4, '2024-01-17 10:30:00'),
(3, 5, '2024-01-18 12:15:00'),
(3, 8, '2024-01-19 14:00:00');

-- User 4 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(4, 1, '2024-01-17 09:20:00'),
(4, 3, '2024-01-18 11:40:00'),
(4, 6, '2024-01-19 13:50:00');

-- User 5 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(5, 1, '2024-01-18 10:10:00'),
(5, 2, '2024-01-19 12:30:00'),
(5, 5, '2024-01-20 14:45:00'),
(5, 9, '2024-01-21 16:20:00');

-- User 6 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(6, 3, '2024-01-19 08:30:00'),
(6, 5, '2024-01-20 10:15:00'),
(6, 10, '2024-01-21 12:00:00');

-- User 7 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(7, 1, '2024-01-20 09:45:00'),
(7, 4, '2024-01-21 11:30:00'),
(7, 7, '2024-01-22 13:15:00');

-- User 8 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(8, 2, '2024-01-21 10:20:00'),
(8, 5, '2024-01-22 12:10:00'),
(8, 8, '2024-01-23 14:30:00');

-- User 9 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(9, 1, '2024-01-22 11:00:00'),
(9, 3, '2024-01-23 13:20:00');

-- User 10 favorites campaigns
INSERT INTO Campaign_Favorites (user_id, campaign_id, favorited_at) VALUES
(10, 5, '2024-01-23 09:30:00'),
(10, 6, '2024-01-24 11:45:00');

-- Summary: 
-- Campaign 1: 7 favorites (most popular!)
-- Campaign 5: 7 favorites (most popular!)
-- Campaign 3: 4 favorites
-- Campaign 2: 3 favorites
-- Campaign 4: 2 favorites
-- Campaign 6: 2 favorites
-- Campaign 7: 2 favorites
-- Campaign 8: 2 favorites
-- Campaign 9: 1 favorite
-- Campaign 10: 1 favorite
