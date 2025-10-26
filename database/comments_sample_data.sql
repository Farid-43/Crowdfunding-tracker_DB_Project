-- Clear existing comments
TRUNCATE TABLE Comments;

-- Insert 25 comments across different campaigns
-- Campaign 1: Jazz Album (4 comments including 1 reply)
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(1, 2, NULL, 'This jazz album sounds amazing! I love supporting local artists. Can\'t wait to hear the final product!', '2024-01-15 10:30:00'),
(1, 3, NULL, 'The midnight blue theme is so unique. When do you expect to complete the recording?', '2024-01-16 14:20:00'),
(1, 4, 1, 'I agree! John is such a talented musician. I saw him perform live last year.', '2024-01-16 16:45:00'),
(1, 5, NULL, 'Just backed this project! Keep up the great work!', '2024-01-18 09:15:00');

-- Campaign 2: Tech Startup (3 comments)
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(2, 6, NULL, 'Innovative idea! How will this differ from existing tech solutions in the market?', '2024-02-10 11:00:00'),
(2, 7, NULL, 'I work in tech and this looks promising. What\'s your timeline for the beta release?', '2024-02-11 13:30:00'),
(2, 2, NULL, 'Excited to see where this goes. The demo video was very convincing!', '2024-02-12 08:45:00');

-- Campaign 3: Children's Book (5 comments including 2 replies)
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(3, 3, NULL, 'My kids will love this! The illustrations look beautiful. Do you have a sample chapter?', '2024-03-05 15:20:00'),
(3, 4, NULL, 'Supporting this for my niece. She loves magical stories!', '2024-03-06 10:00:00'),
(3, 5, 8, 'Yes! Check the updates section, the author posted the first chapter yesterday.', '2024-03-06 14:30:00'),
(3, 6, NULL, 'Will there be a hardcover edition available?', '2024-03-07 09:15:00'),
(3, 2, 10, 'According to the rewards tier, yes! The $50 pledge gets you a signed hardcover.', '2024-03-07 11:00:00');

-- Campaign 4: Eco-Friendly Water Bottle (4 comments)
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(4, 7, NULL, 'Finally! A water bottle that\'s truly sustainable. What materials are you using?', '2024-04-12 12:30:00'),
(4, 1, NULL, 'I\'ve been looking for something like this. How long does it keep drinks cold?', '2024-04-13 16:45:00'),
(4, 3, NULL, 'Love the eco-friendly approach! Will you ship internationally?', '2024-04-14 10:20:00'),
(4, 4, NULL, 'Great design! Is it dishwasher safe?', '2024-04-15 14:00:00');

-- Campaign 5: Smart Home Hub (5 comments including 1 reply)
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(5, 5, NULL, 'This is exactly what I need for my smart home setup! Does it work with Alexa?', '2024-05-20 09:00:00'),
(5, 6, NULL, 'Impressive features! What\'s the warranty period?', '2024-05-21 11:30:00'),
(5, 7, 16, 'According to the FAQ, it has a 2-year warranty with optional extended coverage.', '2024-05-21 13:15:00'),
(5, 2, NULL, 'The energy monitoring feature is a game changer! Pledged for two units.', '2024-05-22 15:45:00'),
(5, 3, NULL, 'Will there be software updates after launch?', '2024-05-23 10:00:00');

-- Campaign 6: Indie Game (4 comments including 1 reply)
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(6, 4, NULL, 'The pixel art style is gorgeous! Reminds me of classic 90s games.', '2024-06-08 14:20:00'),
(6, 5, NULL, 'What platforms will this be available on? Steam?', '2024-06-09 10:45:00'),
(6, 6, 21, 'The campaign page says Steam, Nintendo Switch, and potentially PS5!', '2024-06-09 12:30:00'),
(6, 7, NULL, 'Love the soundtrack preview! Will the OST be available separately?', '2024-06-10 16:00:00');

-- Additional comments on other campaigns
INSERT INTO Comments (campaign_id, user_id, parent_comment_id, content, comment_date) VALUES
(7, 1, NULL, 'This documentary is so important! Ocean conservation needs more awareness.', '2024-07-15 11:00:00'),
(8, 2, NULL, 'Looking forward to this! When is the estimated delivery date?', '2024-08-20 13:45:00');

-- Verify count
SELECT 'Total Comments Inserted:' as Info, COUNT(*) as Count FROM Comments;
