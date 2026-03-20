-- ============================================
-- MIGRATION 019: Glossary System (Simplified)
-- ============================================
-- Simple glossary for social media analytics terms
-- Created: 2026-02-07
-- Purpose: Help users understand analytics terminology
-- ============================================

USE social_media_analytics;

-- ============================================
-- GLOSSARY TABLE
-- ============================================
-- Global definitions (not client-specific)
-- ============================================

CREATE TABLE IF NOT EXISTS glossary (
  id INT PRIMARY KEY AUTO_INCREMENT,
  term VARCHAR(100) UNIQUE NOT NULL,
  definition TEXT NOT NULL,
  example TEXT,
  category ENUM('metric', 'platform', 'general', 'business') DEFAULT 'general',
  platforms JSON,  -- ['tiktok', 'instagram', 'facebook'] or NULL for all
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_term (term),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- INSERT 50 COMMON TERMS
-- ============================================

INSERT INTO glossary (term, definition, example, category, platforms) VALUES

-- METRICS (Engagement)
('Engagement Rate', 'Het percentage mensen dat interacteert met je content (likes, comments, shares) in verhouding tot het aantal mensen dat het ziet.', 'Een post met 100 likes en 5000 views heeft een engagement rate van 2%.', 'metric', '["all"]'),
('Likes', 'Het aantal keer dat mensen op het hartje/duimpje klikken bij je post.', 'Een populaire pizza foto kan 500+ likes krijgen.', 'metric', '["all"]'),
('Comments', 'Het aantal reacties die mensen achterlaten onder je post.', '25 comments op een post over een nieuwe pizza betekent veel interesse.', 'metric', '["all"]'),
('Shares', 'Het aantal keer dat mensen je content delen met hun volgers.', 'Een grappige video die 50 keer gedeeld wordt bereikt veel meer mensen.', 'metric', '["all"]'),
('Saves', 'Het aantal keer dat mensen je post bewaren/bookmarken om later terug te bekijken.', 'Een recept post met 100 saves betekent dat mensen het willen uitproberen.', 'metric', '["instagram", "tiktok"]'),

-- METRICS (Reach & Views)
('Reach', 'Het aantal unieke personen dat je post heeft gezien (1 persoon = 1 reach, ook al ziet hij het 3x).', 'Een reach van 10.000 betekent dat 10.000 verschillende mensen je post zagen.', 'metric', '["instagram", "facebook"]'),
('Impressions', 'Het totaal aantal keer dat je post is getoond (1 persoon kan 3 impressions genereren als hij je post 3x ziet).', 'Impressions zijn altijd hoger of gelijk aan reach.', 'metric', '["instagram", "facebook"]'),
('Views', 'Het aantal keer dat je video is afgespeeld.', 'Een TikTok video met 50.000 views heeft veel mensen bereikt.', 'metric', '["tiktok", "instagram", "youtube"]'),
('Organic Reach', 'Het aantal mensen dat je post ziet zonder dat je ervoor betaalt (dus via normale algoritmes).', 'Een organische reach van 5000 is gratis bereik zonder advertenties.', 'metric', '["all"]'),
('Paid Reach', 'Het aantal mensen dat je post ziet omdat je ervoor betaalt met advertenties.', 'Met €20 advertentiebudget kun je 2000 extra mensen bereiken.', 'metric', '["all"]'),

-- METRICS (Quality - 2026 Algorithm)
('Completion Rate', 'Het percentage mensen dat je video helemaal tot het einde bekijkt. Dit is ZEER belangrijk voor het TikTok/Instagram algoritme in 2026.', 'Een completion rate van 75% betekent dat 3 van de 4 mensen je video helemaal uitkijken - uitstekend!', 'metric', '["tiktok", "instagram", "youtube"]'),
('Watch Time', 'De totale tijd dat alle mensen samen je video hebben gekeken.', 'Een video van 30 seconden die 1000x volledig bekeken wordt heeft 30.000 seconden (500 minuten) watch time.', 'metric', '["tiktok", "instagram", "youtube"]'),
('Skip Rate', 'Het percentage mensen dat voorbij je Reel scrolt zonder deze af te kijken. Belangrijk voor Instagram Reels in 2026.', 'Een skip rate van 15% betekent dat 15 van de 100 mensen je Reel overslaan.', 'metric', '["instagram"]'),
('Sends', 'Het aantal keer dat mensen je post via DM doorsturen naar vrienden. Dit is het STERKSTE engagement signaal voor het algoritme.', 'Een post die 50 keer via DM gedeeld wordt krijgt enorm veel extra bereik.', 'metric', '["instagram", "tiktok"]'),
('Profile Visits', 'Het aantal mensen dat naar je profiel gaat na het zien van je post.', '100 profile visits na een post betekent dat mensen meer willen weten over je restaurant.', 'metric', '["instagram", "tiktok"]'),

-- METRICS (Growth)
('Followers', 'Het aantal mensen dat je account volgt.', 'Van 500 naar 1000 followers in een maand is gezonde groei.', 'metric', '["all"]'),
('Follower Growth', 'Het aantal nieuwe volgers dat je krijgt in een bepaalde periode.', 'Een groeispurt van +200 volgers na een virale video.', 'metric', '["all"]'),

-- METRICS (Business)
('CTR', 'Click-Through Rate: Het percentage mensen dat op een link klikt in je bio of post.', 'Een CTR van 5% op je "Reserveer nu" link betekent dat 5 van de 100 mensen klikken.', 'metric', '["all"]'),
('Conversion', 'Het aantal mensen dat een gewenste actie uitvoert (reservering, aankoop, etc.).', '10 reserveringen uit 1000 bereik = 1% conversie.', 'business', '["all"]'),
('ROI', 'Return On Investment: Hoeveel je terugverdient per euro die je investeert in marketing.', 'Met €50 aan advertenties 10 nieuwe klanten werven die elk €30 uitgeven = €300 omzet = 6x ROI.', 'business', '["all"]'),

-- PLATFORMS
('TikTok', 'Video platform populair bij jongeren, focus op korte entertainment videos (15-60 seconden).', 'Perfect voor behind-the-scenes pizza maken videos.', 'platform', '["tiktok"]'),
('Instagram', 'Visueel platform met Feed posts, Stories en Reels (korte videos).', 'Ideaal voor mooie pizza foto\'s en restaurant sfeer.', 'platform', '["instagram"]'),
('Instagram Feed', 'De standaard Instagram tijdlijn met foto\'s en carrousels die permanent blijven staan.', 'Een mooie foto van je Margherita pizza in de Feed.', 'platform', '["instagram"]'),
('Instagram Stories', 'Tijdelijke content (24 uur) bovenaan Instagram, informeel en dagelijks.', 'Daily special pizza van vandaag via Stories.', 'platform', '["instagram"]'),
('Instagram Reels', 'Korte entertainment videos op Instagram (max 90 seconden), TikTok concurrent.', 'Een Reel van pizza deeg gooien kan viraal gaan.', 'platform', '["instagram"]'),
('Facebook', 'Breder sociaal platform, populair bij 30+ doelgroep, goed voor lokale bedrijven.', 'Lokale pizzeria posts bereiken buurtbewoners.', 'platform', '["facebook"]'),
('YouTube', 'Video platform voor langere content (1-30 minuten).', 'Tutorial: Hoe maak je de perfecte Napolitaanse pizza.', 'platform', '["youtube"]'),

-- GENERAL ANALYTICS
('KPI', 'Key Performance Indicator: De belangrijkste cijfers die je succes meet.', 'Voor een restaurant: followers groei, engagement rate, website clicks.', 'general', '["all"]'),
('Analytics', 'Het verzamelen en analyseren van data over je social media prestaties.', 'Weekly je TikTok analytics checken om trends te zien.', 'general', '["all"]'),
('Benchmark', 'Een referentiewaarde om te vergelijken of je goed of slecht presteert.', 'TikTok gemiddelde engagement is 3.7%, jij haalt 7.5% = 2× beter!', 'general', '["all"]'),
('Algorithm', 'De intelligente software die bepaalt welke content mensen te zien krijgen.', 'Het TikTok algoritme pusht videos met hoge completion rate.', 'general', '["all"]'),
('Hashtag', 'Een keyword voorafgegaan door # om content vindbaar te maken.', '#NapolitaansePizza helpt mensen je pizza posts te vinden.', 'general', '["all"]'),
('Trending', 'Populaire onderwerpen, sounds of hashtags van dit moment.', 'Een trending sound gebruiken kan je 10× meer bereik geven.', 'general', '["all"]'),

-- CONTENT TYPES
('Reel', 'Korte verticale video (15-90 sec) op Instagram, vergelijkbaar met TikTok.', 'Een 30 seconden Reel van pizza uit de oven.', 'general', '["instagram"]'),
('Story', 'Tijdelijke content (24u) bovenaan Instagram/Facebook, informeel.', 'Daily special via Stories posten.', 'general', '["instagram", "facebook"]'),
('Carousel', 'Een post met meerdere foto\'s/videos die je kan doorswipen.', 'Een carrousel met 5 foto\'s van verschillende pizza\'s.', 'general', '["instagram", "facebook"]'),
('Feed Post', 'Een standaard foto/video post die permanent op je profiel blijft staan.', 'Een prachtige foto van je beste pizza als Feed post.', 'general', '["all"]'),

-- AUDIENCE
('Audience', 'De groep mensen die je content volgt of ziet.', 'Je audience is 70% vrouw, 30% man, leeftijd 25-40.', 'general', '["all"]'),
('Demographics', 'Informatie over je publiek: leeftijd, geslacht, locatie, etc.', 'Demographics tonen dat 80% van je volgers uit Leuven komt.', 'general', '["all"]'),
('Target Audience', 'De specifieke groep mensen die je wilt bereiken met je content.', 'Target audience: Leuvenaren 25-45 jaar die van Italiaans eten houden.', 'business', '["all"]'),

-- BUSINESS TERMS
('Content Strategy', 'Het plan dat bepaalt wat, wanneer en hoe je post op social media.', 'Elke dinsdag een nieuwe pizza Reel, donderdag behind-the-scenes Story.', 'business', '["all"]'),
('Engagement', 'Alle interacties samen: likes, comments, shares, saves, clicks.', 'Een post met 500 likes + 50 comments + 20 shares = 570 engagement.', 'metric', '["all"]'),
('Viral', 'Content die explosief veel mensen bereikt in korte tijd.', 'Een video die van 1000 naar 100.000 views gaat in 2 dagen is viraal gegaan.', 'general', '["all"]'),
('Call-to-Action', 'Een duidelijke instructie wat je wilt dat mensen doen (reserveer, volg, klik).', '"Swipe omhoog voor het menu" of "Tag een vriend die dit moet proberen".', 'business', '["all"]'),
('User-Generated Content', 'Content die je klanten over jou maken (foto\'s, reviews, tags).', 'Een klant post een foto van je pizza en tagged je restaurant.', 'general', '["all"]'),

-- POSTING & TIMING
('Optimal Posting Time', 'Het tijdstip waarop je posts het beste presteren bij jouw publiek.', 'Voor een lunchroom: 11:00-12:00 als mensen honger krijgen.', 'general', '["all"]'),
('Consistency', 'Regelmatig en voorspelbaar posten volgens een schema.', '3 posts per week op vaste dagen zorgt voor groei.', 'general', '["all"]');


-- ============================================
-- MIGRATION COMPLETE
-- ============================================

SELECT 'Migration 019 completed successfully!' as status;
SELECT 'Table created: glossary' as table_created;
SELECT COUNT(*) as total_terms FROM glossary;
SELECT 'Categories: metric, platform, general, business' as categories;
