USE inventory;
DELETE FROM category;
INSERT INTO category (category_code, category_name) VALUES
('CAT-001','Electronics'),
('CAT-002','Food & Beverage'),
('CAT-003','Clothing'),
('CAT-004','Health & Wellness'),
('CAT-005','Books & Stationery');
