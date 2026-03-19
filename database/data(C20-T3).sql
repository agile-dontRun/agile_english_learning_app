USE english_learning_app;

START TRANSACTION;

-- =====================================
-- Cambridge IELTS 20 - Test 1 - Part 1
-- =====================================
SET @part1_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 1
      AND part_no = 1
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part1_id, '/default/Video/IELTS_QUESTION/C20-T1-P1.png', 1, 'question', 'Cambridge 20 Test 1 Part 1 question image');

-- Part 1 answers: 请把下面答案替换成你自己的真实答案
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part1_id, 1, 'blank_fill', 'fish', NULL),
(@part1_id, 2, 'blank_fill', 'roof', NULL),
(@part1_id, 3, 'blank_fill', 'Spanish', NULL),
(@part1_id, 4, 'blank_fill', 'vegetarian', NULL),
(@part1_id, 5, 'blank_fill', 'Audley', NULL),
(@part1_id, 6, 'blank_fill', 'hotel', NULL),
(@part1_id, 7, 'blank_fill', 'reviews', NULL),
(@part1_id, 8, 'blank_fill', 'local', NULL),
(@part1_id, 9, 'blank_fill', '30', NULL),
(@part1_id, 10, 'blank_fill', 'average', NULL);

-- =====================================
-- Cambridge IELTS 20 - Test 1 - Part 2
-- =====================================
SET @part2_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 1
      AND part_no = 2
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part2_id, '/default/Video/IELTS_QUESTION/C20-T1-P2-1.png', 1, 'question', 'Cambridge 20 Test 1 Part 2 question image'),
(@part2_id, '/default/Video/IELTS_QUESTION/C20-T1-P2-2.png', 2, 'question', 'Cambridge 20 Test 1 Part 2 question image'),
(@part2_id, '/default/Video/IELTS_QUESTION/C20-T1-P2-3.png', 3, 'question', 'Cambridge 20 Test 1 Part 2 question image');

-- Part 2 answers
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part2_id, 11, 'choice', 'A', NULL),
(@part2_id, 12, 'choice', 'B', NULL),
(@part2_id, 13, 'choice', 'C', NULL),
(@part2_id, 14, 'choice', 'A', NULL),
(@part2_id, 15, 'choice', 'B', NULL),
(@part2_id, 16, 'choice', 'C', NULL),
(@part2_id, 17, 'choice', 'A', NULL),
(@part2_id, 18, 'choice', 'E', NULL),
(@part2_id, 19, 'choice', 'C', NULL),
(@part2_id, 20, 'choice', 'E', NULL);

-- =====================================
-- Cambridge IELTS 20 - Test 1 - Part 3
-- =====================================
SET @part3_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 1
      AND part_no = 3
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part3_id, '/default/Video/IELTS_QUESTION/C20-T1-P3-1.png', 1, 'question', 'Cambridge 20 Test 1 Part 3 question image'),
(@part3_id, '/default/Video/IELTS_QUESTION/C20-T1-P3-2.png', 2, 'question', 'Cambridge 20 Test 1 Part 3 question image'),
(@part3_id, '/default/Video/IELTS_QUESTION/C20-T1-P3-3.png', 3, 'question', 'Cambridge 20 Test 1 Part 3 question image');

-- Part 3 answers
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part3_id, 21, 'choice', 'C', NULL),
(@part3_id, 22, 'choice', 'E', NULL),
(@part3_id, 23, 'choice', 'A', NULL),
(@part3_id, 24, 'choice', 'C', NULL),
(@part3_id, 25, 'choice', 'A', NULL),
(@part3_id, 26, 'choice', 'B', NULL),
(@part3_id, 27, 'choice', 'A', NULL),
(@part3_id, 28, 'choice', 'B', NULL),
(@part3_id, 29, 'choice', 'A', NULL),
(@part3_id, 30, 'choice', 'C', NULL);

-- =====================================
-- Cambridge IELTS 20 - Test 1 - Part 4
-- =====================================
SET @part4_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 1
      AND part_no = 4
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part4_id, '/default/Video/IELTS_QUESTION/C20-T1-P4-1.png', 1, 'question', 'Cambridge 20 Test 1 Part 4 question image'),
(@part4_id, '/default/Video/IELTS_QUESTION/C20-T1-P4-2.png', 2, 'question', 'Cambridge 20 Test 1 Part 4 question image');

-- Part 4 answers
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part4_id, 31, 'blank_fill', 'factories', NULL),
(@part4_id, 32, 'blank_fill', 'dead', NULL),
(@part4_id, 33, 'blank_fill', 'whale', NULL),
(@part4_id, 34, 'blank_fill', 'apartments', NULL),
(@part4_id, 35, 'blank_fill', 'park', NULL),
(@part4_id, 36, 'blank_fill', 'art', NULL),
(@part4_id, 37, 'blank_fill', 'beaches', NULL),
(@part4_id, 38, 'blank_fill', 'ferry', NULL),
(@part4_id, 39, 'blank_fill', 'bikes', NULL),
(@part4_id, 40, 'blank_fill', 'drone', NULL);

COMMIT;