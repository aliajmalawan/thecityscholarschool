-- ---------------------------------------------------------------------------
-- Delete the 12 demo courses that came with the website template
-- (Matric/Intermediate/ECAT/MDCAT/IELTS ... all created on 2026-09-11).
--
-- Run once in phpMyAdmin (SQL tab). Courses the school added itself are kept:
-- a course is only deleted when BOTH its name is one of the demo names AND it
-- was created before 1 October 2026 (before the City Scholars website).
-- ---------------------------------------------------------------------------
DELETE FROM `courses`
WHERE `created_at` < '2026-10-01'
  AND `name` IN ('Matric Programs (9th & 10th)', 'Intermediate - Pre-Engineering', 'Intermediate - Pre-Medical',
                 'Intermediate - Computer Science', 'ECAT Preparation', 'MDCAT Preparation', 'Computer Programming',
                 'Web Development', 'Spoken English', 'IELTS Preparation', 'Advanced Mathematics', 'Physics & Chemistry');

-- What is left (should be only the school's own courses):
SELECT `id`, `name`, `status`, `created_at` FROM `courses` ORDER BY `id`;
