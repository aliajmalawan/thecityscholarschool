-- ---------------------------------------------------------------------------
-- Inner pages (The City Scholars School)
--
-- Run once in phpMyAdmin (SQL tab) after homepage-redesign.sql. Safe to run again.
-- Nothing is deleted: entries inherited from the previous school that state prices
-- or promises are only switched OFF, so they can be checked and switched back on
-- in the admin panel if they are correct for City Scholars.
--
--   1. Fee items (Rs. 2,000 admission fee, Rs. 5,000 monthly for Matric, ...):
--      switched off. The Admission page then shows "Fee schedule on request" until
--      the school adds its own fees (AdminCP -> Fee Items).
--   2. FAQs that promise scholarships or quote fees: switched off.
--   3. Faculty highlights "Highly Qualified" and "Experienced Professionals":
--      switched off (claims about staff that cannot be checked yet).
--   4. Demo content from the website template (sample teachers, alumni, reviews,
--      news, events, course catalogue, campuses): hidden, see section 4 below.
-- ---------------------------------------------------------------------------

-- 1. Fees
UPDATE `fee_items` SET `status` = 'inactive'
    WHERE `label` IN ('Admission Fee (One Time)', 'Matric Programs (Monthly)', 'Intermediate Programs (Monthly)',
                      'Entry Test Preparation (Monthly)', 'Short Courses (Monthly)');

-- 2. FAQs with promises about fees and scholarships
UPDATE `faqs` SET `status` = 'inactive'
    WHERE `question` IN ('What is the fee structure?', 'Do you offer scholarships?');

-- 3. Faculty highlights that make claims about staff
UPDATE `feature_cards` SET `status` = 'inactive'
    WHERE `section` = 'faculty_highlights' AND `title` IN ('Highly Qualified', 'Experienced Professionals');

-- 4. Sample content that came with the website template ---------------------------
--    These records were all created at the same moment as demo data (the teachers
--    are the same names the old Faculty page used as made-up fallbacks). Showing them
--    would present fictional teachers, alumni, news and events as real, so they are
--    hidden. Every page then shows a friendly "coming soon" message until real
--    entries are added. To bring one back: switch it to Active (or Approved) in the
--    admin panel.

-- Teachers (AdminCP -> Faculty)
UPDATE `faculty` SET `status` = 'inactive'
    WHERE `name` IN ('Prof. Muhammad Ahmed', 'Dr. Fatima Khan', 'Sir Usman Ali', 'Miss Ayesha Malik',
                     'Sir Hassan Raza', 'Sir Bilal Iqbal', 'Miss Zainab Shah', 'Sir Kamran Abbas');

-- Alumni and their reviews: back to "pending" so they can be approved again if real
UPDATE `alumni` SET `status` = 'pending'
    WHERE `status` = 'approved' AND `student_name` IN ('Ahmed Raza', 'Ayesha Malik', 'Bilal Hussain', 'Sana Iqbal', 'Usman Tariq', 'Fatima Noor');
UPDATE `alumni_reviews` SET `status` = 'pending'
    WHERE `status` = 'approved' AND `name` IN ('Ahmed Raza', 'Ayesha Malik', 'Bilal Hussain', 'Sana Iqbal', 'Usman Tariq');

-- News and events
UPDATE `news` SET `status` = 'inactive'
    WHERE `title` IN ('The City Scholars School Achieves Excellent Board Results', 'Tameer-e-Nau Public School Achieves Excellent Board Results',
                      'New Computer Lab Inaugurated', 'Admissions Open for New Academic Session', 'School Wins Inter-School Debate Competition');
UPDATE `events` SET `status` = 'inactive'
    WHERE `title` IN ('Annual Sports Day', 'Parent-Teacher Meeting', 'Science Exhibition', 'Annual Prize Distribution Ceremony');

-- Course catalogue (college-level programs such as MDCAT, ECAT and IELTS preparation).
-- While no course is active, the Academics page shows the school levels and the
-- admission form offers those levels instead.
UPDATE `courses` SET `status` = 'inactive'
    WHERE `name` IN ('Matric Programs (9th & 10th)', 'Intermediate - Pre-Engineering', 'Intermediate - Pre-Medical',
                     'Intermediate - Computer Science', 'ECAT Preparation', 'MDCAT Preparation', 'Computer Programming',
                     'Web Development', 'Spoken English', 'IELTS Preparation', 'Advanced Mathematics', 'Physics & Chemistry');

-- Sister institutes that belong to the template's demo data
UPDATE `campuses` SET `status` = 'inactive' WHERE `name` IN ('Computer College', 'Taekwondo Campus');

-- Admission requirements for programs the school is not known to offer
UPDATE `feature_cards` SET `status` = 'inactive'
    WHERE `section` = 'admission_requirements' AND `title` IN ('For Intermediate Programs', 'For Short Courses');
