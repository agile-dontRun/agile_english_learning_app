USE english_learning_app;

START TRANSACTION;

-- =====================================
-- Cambridge IELTS 20 - Test 3 - Part 1
-- =====================================
SET @part1_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 3
      AND part_no = 1
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part1_id, '/default/Video/IELTS_QUESTION/P1-1.png', 1, 'question', 'Cambridge 20 Test 3 Part 1 question image');

-- Part 1 answers: 请把下面答案替换成你自己的真实答案
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part1_id, 1, 'blank_fill', '239|two hundred thirty-nine|two hundred and thirty-nine', NULL),
(@part1_id, 2, 'blank_fill', 'mordern', NULL),
(@part1_id, 3, 'blank_fill', 'lamp', NULL),
(@part1_id, 4, 'blank_fill', 'Aaron', NULL),
(@part1_id, 5, 'blank_fill', 'damage', NULL),
(@part1_id, 6, 'blank_fill', 'electronic', NULL),
(@part1_id, 7, 'blank_fill', 'insurance', NULL),
(@part1_id, 8, 'blank_fill', 'Space', NULL),
(@part1_id, 9, 'blank_fill', 'app', NULL),
(@part1_id, 10, 'blank_fill', 'Exchanges', NULL);

-- =====================================
-- Cambridge IELTS 20 - Test 3 - Part 2
-- =====================================
SET @part2_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 3
      AND part_no = 2
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part2_id, '/default/Video/IELTS_QUESTION/P2-1.png', 1, 'question', 'Cambridge 20 Test 3 Part 2 question image'),
(@part2_id, '/default/Video/IELTS_QUESTION/P2-2.png', 2, 'question', 'Cambridge 20 Test 3 Part 2 question image'),
(@part2_id, '/default/Video/IELTS_QUESTION/P2-3.png', 3, 'question', 'Cambridge 20 Test 3 Part 2 question image'),
(@part2_id, '/default/Video/IELTS_QUESTION/P2-4.png', 4, 'question', 'Cambridge 20 Test 3 Part 2 question image');

-- Part 2 answers
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part2_id, 11, 'choice', 'B', NULL),
(@part2_id, 12, 'choice', 'A', NULL),
(@part2_id, 13, 'choice', 'A', NULL),
(@part2_id, 14, 'choice', 'C', NULL),
(@part2_id, 15, 'choice', 'B', NULL),
(@part2_id, 16, 'choice', 'C', NULL),
(@part2_id, 17, 'choice', 'B', NULL),
(@part2_id, 18, 'choice', 'A', NULL),
(@part2_id, 19, 'choice', 'G', NULL),
(@part2_id, 20, 'choice', 'E', NULL);

-- =====================================
-- Cambridge IELTS 20 - Test 3 - Part 3
-- =====================================
SET @part3_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 3
      AND part_no = 3
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part3_id, '/default/Video/IELTS_QUESTION/P3-1.png', 1, 'question', 'Cambridge 20 Test 3 Part 3 question image'),
(@part3_id, '/default/Video/IELTS_QUESTION/P3-2.png', 2, 'question', 'Cambridge 20 Test 3 Part 3 question image'),
(@part3_id, '/default/Video/IELTS_QUESTION/P3-3.png', 3, 'question', 'Cambridge 20 Test 3 Part 3 question image');

-- Part 3 answers
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part3_id, 21, 'choice', 'B', NULL),
(@part3_id, 22, 'choice', 'A', NULL),
(@part3_id, 23, 'choice', 'C', NULL),
(@part3_id, 24, 'choice', 'A', NULL),
(@part3_id, 25, 'choice', 'C', NULL),
(@part3_id, 26, 'choice', 'B', NULL),
(@part3_id, 27, 'choice', 'F', NULL),
(@part3_id, 28, 'choice', 'E', NULL),
(@part3_id, 29, 'choice', 'B', NULL),
(@part3_id, 30, 'choice', 'D', NULL);

-- =====================================
-- Cambridge IELTS 20 - Test 3 - Part 4
-- =====================================
SET @part4_id = (
    SELECT part_id
    FROM ielts_listening_parts
    WHERE cambridge_no = 20
      AND test_no = 3
      AND part_no = 4
);

INSERT INTO ielts_part_images (
    part_id,
    image_url,
    image_order,
    image_type,
    description
) VALUES
(@part4_id, '/default/Video/IELTS_QUESTION/P4-1.png', 1, 'question', 'Cambridge 20 Test 3 Part 4 question image'),
(@part4_id, '/default/Video/IELTS_QUESTION/P4-2.png', 2, 'question', 'Cambridge 20 Test 3 Part 4 question image');

-- Part 4 answers
INSERT INTO ielts_answers (
    part_id,
    question_no,
    answer_type,
    correct_answer,
    explanation
) VALUES
(@part4_id, 31, 'blank_fill', 'adaptation', NULL),
(@part4_id, 32, 'blank_fill', 'cognitive', NULL),
(@part4_id, 33, 'blank_fill', 'Desks', NULL),
(@part4_id, 34, 'blank_fill', 'Taps', NULL),
(@part4_id, 35, 'blank_fill', 'blue', NULL),
(@part4_id, 36, 'blank_fill', 'voice', NULL),
(@part4_id, 37, 'blank_fill', 'pregnant', NULL),
(@part4_id, 38, 'blank_fill', 'shoulders', NULL),
(@part4_id, 39, 'blank_fill', 'police', NULL),
(@part4_id, 40, 'blank_fill', 'temperature', NULL);

COMMIT;