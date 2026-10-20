<?php
/**
 * Registry of every piece of public-site content that is editable from
 * AdminCP -> Page Content (manage_content.php).
 *
 * Each field: key (settings table), label, type, default (what the site shows
 * until someone saves a value) and an optional hint.
 *   type: text | textarea | lines (one item per line) | url | image | logo
 * Defaults may use {site_name}, replaced with the site name at render time.
 * Values are stored in the existing `settings` table, so no schema change.
 */

/** Small helper so the registry below stays readable. */
function cf($key, $label, $type, $default, $hint = '') {
    return ['key' => $key, 'label' => $label, 'type' => $type, 'default' => $default, 'hint' => $hint];
}

/** Banner + SEO fields shared by every inner page. */
function pageFields($slug, $eyebrow, $title, $subtitle, $seoTitle, $seoDesc) {
    return [
        cf("banner_{$slug}_image", 'Banner photo', 'image', "images/site/banner-{$slug}.jpg", 'Wide photo shown behind the page title (landscape, at least 1400px wide works best).'),
        cf("banner_{$slug}_eyebrow", 'Banner small label', 'text', $eyebrow),
        cf("banner_{$slug}_title", 'Banner title (main heading)', 'text', $title),
        cf("banner_{$slug}_subtitle", 'Banner subtitle', 'text', $subtitle),
        cf("seo_{$slug}_title", 'Browser / Google title', 'text', $seoTitle, 'Shown on the browser tab and in search results. "| School name" is added automatically.'),
        cf("seo_{$slug}_description", 'Google description', 'textarea', $seoDesc, 'Aim for 120-155 characters.'),
    ];
}

$registry = [
    'global' => ['label' => 'Site-wide', 'icon' => 'fa-globe', 'fields' => [
        cf('site_logo', 'School logo', 'logo', 'images/city-scholars-crest.png', 'Square, round or crest logo (PNG/JPG/WebP, up to 8 MB; resized and compressed automatically). Used in the header, footer, emails and share previews. The browser-tab icon is changed under Logo & Favicon.'),
        cf('site_tagline', 'Tagline under the school name', 'text', 'Excellence in Education', 'Small line below the name in the header and footer.'),
        cf('seo_title_suffix', 'Home page title suffix', 'text', 'Excellence in Education', 'Home page browser title becomes "School name | this text".'),
        cf('apply_button_label', 'Header "Apply" button text', 'text', 'Apply Now'),
        cf('seo_home_description', 'Home page Google description', 'textarea', '{site_name} offers quality education with dedicated teachers, a safe and disciplined campus and a strong focus on character and academic excellence.'),
    ]],

    'home_hero' => ['label' => 'Home: Hero & About', 'icon' => 'fa-house', 'fields' => [
        cf('home_hero_eyebrow', 'Hero small label', 'text', 'Welcome to {site_name}'),
        cf('hero_title', 'Hero headline', 'text', 'Inspiring Young Minds. Building Bright Futures.', 'Everything after the first full stop is shown in gold.'),
        cf('hero_description', 'Hero text', 'textarea', 'At {site_name}, children learn in a caring, disciplined environment where strong academics and good character grow together.'),
        cf('hero_image', 'Fallback hero photo', 'image', '', 'Only used when there are no active slides. The background photos themselves are managed under Home Slider in the sidebar.'),
        cf('home_hero_btn1_label', 'Hero button 1 text (gold)', 'text', 'Apply for Admission'),
        cf('home_hero_btn1_link', 'Hero button 1 link', 'url', 'admission'),
        cf('home_hero_btn2_label', 'Hero button 2 text (outline)', 'text', 'Explore Our School'),
        cf('home_hero_btn2_link', 'Hero button 2 link', 'url', 'about'),
        cf('home_hero_points', 'Hero highlights', 'lines', "Knowledge\nCharacter\nExcellence", 'Up to three short words or phrases shown under the buttons, one per line. Leave empty to hide.'),
        cf('home_about_eyebrow', 'About section small label', 'text', 'About Us'),
        cf('home_about_heading', 'About section heading', 'text', 'Welcome to {site_name}'),
        cf('home_about_text', 'About section introduction', 'textarea', '{site_name} is built on the belief that every child deserves a strong start. We combine a disciplined, supportive academic environment with caring teachers, so students grow in knowledge, confidence and character and are ready to contribute positively to their community.'),
        cf('home_about_image', 'About section photo', 'image', 'images/site/about-home.jpg', 'Portrait photo (4:5) looks best.'),
        cf('home_about_btn_label', 'About section button text', 'text', 'Read More About Us'),
        cf('stats_label_students', 'Statistics label: students', 'text', 'Students', 'The statistics band only appears once real numbers are entered in Settings -> Statistics.'),
        cf('stats_label_faculty', 'Statistics label: faculty', 'text', 'Teachers'),
        cf('stats_label_years', 'Statistics label: years', 'text', 'Years of Service'),
        cf('stats_label_programs', 'Statistics label: programs', 'text', 'Programs'),
    ]],

    'home_sections' => ['label' => 'Home: Sections', 'icon' => 'fa-table-cells-large', 'fields' => [
        cf('home_quick_1_title', 'Quick link 1: title', 'text', 'Admissions', 'Quick links on the edge of the hero. Clear the title to hide a card.'),
        cf('home_quick_1_text', 'Quick link 1: text', 'text', 'How to apply, step by step'),
        cf('home_quick_1_link', 'Quick link 1: link', 'url', 'admission'),
        cf('home_quick_2_title', 'Quick link 2: title', 'text', 'Academics'),
        cf('home_quick_2_text', 'Quick link 2: text', 'text', 'Our programs, stage by stage'),
        cf('home_quick_2_link', 'Quick link 2: link', 'url', 'courses'),
        cf('home_quick_3_title', 'Quick link 3: title', 'text', 'About Us'),
        cf('home_quick_3_text', 'Quick link 3: text', 'text', 'Our mission, vision and values'),
        cf('home_quick_3_link', 'Quick link 3: link', 'url', 'about'),
        cf('home_quick_4_title', 'Quick link 4: title', 'text', 'Visit Us'),
        cf('home_quick_4_text', 'Quick link 4: text', 'text', 'Address, map and directions'),
        cf('home_quick_4_link', 'Quick link 4: link', 'url', 'contact'),
        cf('home_academics_eyebrow', 'Academics: small label', 'text', 'Academic Programs'),
        cf('home_academics_heading', 'Academics: heading', 'text', 'Learning at Every Stage'),
        cf('home_academics_subtitle', 'Academics: subtitle', 'text', "A clear path from a child's first years at school to secondary education"),
        cf('academics_1_title', 'Academics card 1: title', 'text', 'Early Years', "Clear a card's title to hide it, so only the levels the school offers are shown."),
        cf('academics_1_desc', 'Academics card 1: text', 'textarea', 'A warm and caring start where young learners build curiosity, early language and social skills.'),
        cf('academics_2_title', 'Academics card 2: title', 'text', 'Primary Education'),
        cf('academics_2_desc', 'Academics card 2: text', 'textarea', 'Strong foundations in literacy, numeracy and values, taught with patience and personal attention.'),
        cf('academics_3_title', 'Academics card 3: title', 'text', 'Middle School'),
        cf('academics_3_desc', 'Academics card 3: text', 'textarea', 'Building confidence, critical thinking and good study habits as students take on broader subjects.'),
        cf('academics_4_title', 'Academics card 4: title', 'text', 'Secondary Education'),
        cf('academics_4_desc', 'Academics card 4: text', 'textarea', 'Focused preparation for the Matric (9th & 10th) examinations.'),
        cf('home_band_eyebrow', 'Photo band: small label', 'text', 'Our Promise', 'The wide photo band between Academic Programs and Why Choose Us. Clear the heading to hide it.'),
        cf('home_band_heading', 'Photo band: heading', 'text', 'Every Child Deserves a Strong Start'),
        cf('home_band_text', 'Photo band: text', 'textarea', 'We know every family wants the best for their child. Come and see our classrooms, meet our teachers and find out how we can help your child grow.'),
        cf('home_band_image', 'Photo band: photo', 'image', 'images/site/banner-campus_life.jpg', 'Wide landscape photo; a navy shade is added so the text stays readable.'),
        cf('home_why_eyebrow', 'Why choose us: small label', 'text', 'Why Choose Us', 'The cards themselves are in Feature Cards -> Why Choose Us.'),
        cf('home_why_heading', 'Why choose us: heading', 'text', 'Why Families Choose Us'),
        cf('home_why_subtitle', 'Why choose us: subtitle', 'text', 'The priorities that shape every school day'),
        cf('home_facilities_eyebrow', 'Facilities: small label', 'text', 'Campus Life', 'Facility cards are in Feature Cards -> Facilities (only active cards are shown, so switch on just the ones the school has). The photo cards come from Gallery categories.'),
        cf('home_facilities_heading', 'Facilities: heading', 'text', 'Facilities & Campus Life'),
        cf('home_facilities_subtitle', 'Facilities: subtitle', 'text', 'Learning happens in classrooms, at events and in everything in between'),
        cf('home_news_eyebrow', 'News & events: small label', 'text', 'Stay Updated'),
        cf('home_news_heading', 'News & events: heading', 'text', 'News & Events'),
        cf('home_news_subtitle', 'News & events: subtitle', 'text', 'Announcements and upcoming dates from {site_name}'),
        cf('home_gallery_eyebrow', 'Gallery: small label', 'text', 'Photo Gallery'),
        cf('home_gallery_heading', 'Gallery: heading', 'text', 'Moments From Our School'),
        cf('home_gallery_subtitle', 'Gallery: subtitle', 'text', 'A glimpse of everyday learning and special occasions'),
    ]],

    'home_admissions' => ['label' => 'Home: Admissions', 'icon' => 'fa-user-plus', 'fields' => [
        cf('home_adm_eyebrow', 'Admissions: small label', 'text', 'Explore Admissions', 'Change to "Admissions Open" only while admissions are actually open.'),
        cf('home_adm_heading', 'Admissions: heading', 'text', "Begin Your Child's Journey With Us"),
        cf('home_adm_text', 'Admissions: introduction', 'textarea', 'Joining {site_name} is simple. Send us an inquiry or apply online, and our admissions office will guide you through the next steps.'),
        cf('home_adm_btn1_label', 'Admissions: button text', 'text', 'Apply Now'),
        cf('home_inquiry_heading', 'Inquiry form: heading', 'text', 'Admission Inquiry'),
        cf('home_inquiry_text', 'Inquiry form: text', 'text', 'Tell us a little about your child and we will contact you.'),
        cf('home_inquiry_classes', 'Inquiry form: class options', 'lines', "Playgroup\nNursery\nKindergarten\nClass 1\nClass 2\nClass 3\nClass 4\nClass 5\nClass 6\nClass 7\nClass 8\nClass 9\nClass 10", 'One class per line, shown in the "Desired class" list. "Not sure yet" is always added at the end.'),
    ]],

    'about' => ['label' => 'About page', 'icon' => 'fa-circle-info', 'fields' => array_merge(
        pageFields('about', 'About Us', 'About {site_name}', 'Learn about our mission, vision, and commitment to educational excellence', 'About Us', 'Learn about {site_name}: our mission, vision and core values, and our commitment to quality education and student development.'),
        [
            cf('about_story_eyebrow', 'Story: small label', 'text', 'Who We Are'),
            cf('about_story_heading', 'Story: heading', 'text', 'Our Story'),
            cf('about_description', 'Story: paragraph 1', 'textarea', ''),
            cf('about_paragraph_2', 'Story: paragraph 2', 'textarea', ''),
            cf('about_paragraph_3', 'Story: paragraph 3', 'textarea', ''),
            cf('about_story_image', 'Story photo', 'image', 'images/site/about-story.jpg'),
            cf('mission_statement', 'Mission statement', 'textarea', ''),
            cf('vision_statement', 'Vision statement', 'textarea', ''),
            cf('principal_message_quote', "Principal's message: quote", 'textarea', '', 'Leave all principal fields empty to hide the card.'),
            cf('principal_message_para_1', "Principal's message: paragraph 1", 'textarea', ''),
            cf('principal_message_para_2', "Principal's message: paragraph 2", 'textarea', ''),
            cf('principal_photo', "Principal's photo", 'image', ''),
        ]
    )],

    'courses' => ['label' => 'Courses page', 'icon' => 'fa-book', 'fields' => array_merge(
        pageFields('courses', 'Academics', 'Academic Programs', 'A clear learning pathway, from the early years to secondary school', 'Academics', 'Explore the courses at {site_name}: Early Years, Primary, Middle School and Matric (Science, Computer Science and Arts groups).'),
        [
            cf('courses_levels_eyebrow', 'School levels: small label', 'text', 'Our Approach', 'The four level cards use the same texts as the homepage (Home: Sections -> Academics cards).'),
            cf('courses_levels_heading', 'School levels: heading', 'text', 'Learning at Every Stage'),
            cf('courses_levels_subtitle', 'School levels: subtitle', 'text', 'Each stage builds on the one before, so children grow with confidence'),
            cf('courses_list_eyebrow', 'Programs list: small label', 'text', 'Programs & Courses', 'The courses themselves are added and edited under Courses in the sidebar.'),
            cf('courses_list_heading', 'Programs list: heading', 'text', 'Our Programs'),
            cf('courses_list_subtitle', 'Programs list: subtitle', 'text', 'Subjects and classes for each program'),
            cf('courses_path_eyebrow', 'Overview: small label', 'text', 'Academic Path'),
            cf('courses_path_heading', 'Overview: heading', 'text', 'From Early Years to Matric'),
            cf('courses_path_subtitle', 'Overview: subtitle', 'text', 'A clear, step-by-step journey through every stage of school'),
            cf('courses_early_years_heading', 'Early Years: heading', 'text', 'Early Years'),
            cf('courses_early_years_subtitle', 'Early Years: subtitle', 'text', 'Playful, caring beginnings for our youngest learners'),
            cf('courses_primary_heading', 'Primary: heading', 'text', 'Primary School'),
            cf('courses_primary_subtitle', 'Primary: subtitle', 'text', 'Building strong foundations in every core subject'),
            cf('courses_middle_heading', 'Middle School: heading', 'text', 'Middle School'),
            cf('courses_middle_subtitle', 'Middle School: subtitle', 'text', 'Growing knowledge, confidence and independence'),
            cf('courses_secondary_heading', 'Matric: heading', 'text', 'Matric (Secondary)'),
            cf('courses_secondary_subtitle', 'Matric: subtitle', 'text', 'Focused preparation for the Matric examinations in three groups'),
            cf('courses_card_btn', 'Course card button text', 'text', 'Apply for admission'),
            cf('courses_req_eyebrow', 'Requirements: small label', 'text', 'Requirements'),
            cf('courses_req_heading', 'Requirements: heading', 'text', 'Admission Requirements'),
            cf('courses_req_subtitle', 'Requirements: subtitle', 'text', 'What you need to know before applying'),
            cf('courses_explore_heading', 'Explore links: heading', 'text', 'Explore Academics'),
            cf('courses_cta_heading', 'Bottom banner: heading', 'text', 'Ready to Enroll?'),
            cf('courses_cta_text', 'Bottom banner: text', 'text', 'Start your learning journey with {site_name} today'),
        ]
    )],
    'admission' => ['label' => 'Admission page', 'icon' => 'fa-file-pen', 'fields' => pageFields('admission', 'Join Us', 'Admission Application', 'Start your educational journey with {site_name}', 'Admissions', 'Apply online to {site_name}. See the admission process, the documents you need and how to reach our admissions office.')],
    'faculty' => ['label' => 'Faculty page', 'icon' => 'fa-chalkboard-user', 'fields' => pageFields('faculty', 'Our Team', 'Our Faculty', 'Meet the teachers who guide our students every day', 'Our Faculty', 'Meet the teachers of {site_name}, dedicated to helping every student learn, grow and succeed.')],
    'campuses' => ['label' => 'Campus page', 'icon' => 'fa-school', 'fields' => pageFields('campuses', 'Our Network', 'Our Campuses', 'Where {site_name} teaches and how to find us', 'Our Campuses', 'Explore the campuses of {site_name}: our main campus and our specialised institutes for computer studies and sports training.')],
    'campus_life' => ['label' => 'Campus Life page', 'icon' => 'fa-people-group', 'fields' => array_merge(
        pageFields('campus_life', 'Campus Life', 'Life at {site_name}', 'Learning, friendships and activities beyond the classroom', 'Campus Life', 'Discover campus life at {site_name}: facilities, school events, activities, photos and our alumni community.'),
        [
            cf('campus_life_intro_eyebrow', 'Introduction: small label', 'text', 'Beyond the Classroom'),
            cf('campus_life_intro_heading', 'Introduction: heading', 'text', 'A School Day Is More Than Lessons'),
            cf('campus_life_intro_text', 'Introduction: text', 'textarea', 'At {site_name}, students learn to work together, take part in activities and celebrate achievements as a community. This page brings together our facilities, events and photos in one place.'),
            cf('campus_life_facilities_heading', 'Facilities: heading', 'text', 'Our Facilities', 'Facility cards are in Feature Cards -> Facilities; only active cards are shown.'),
            cf('campus_life_photos_heading', 'Photos: heading', 'text', 'Moments From School Life'),
            cf('campus_life_explore_heading', 'Explore links: heading', 'text', 'Explore Campus Life'),
            cf('campus_life_cta_heading', 'Bottom banner: heading', 'text', 'Come and See Our School'),
            cf('campus_life_cta_text', 'Bottom banner: text', 'textarea', 'Get in touch to arrange a visit or ask about admissions.'),
        ]
    )],
    'gallery' => ['label' => 'Gallery page', 'icon' => 'fa-images', 'fields' => pageFields('gallery', 'Campus Life', 'Photo Gallery', 'Explore moments captured at {site_name}', 'Photo Gallery', 'Photos and videos of classroom activities, school events and celebrations at {site_name}. See everyday campus life for yourself.')],
    'events' => ['label' => 'News & Events page', 'icon' => 'fa-calendar-days', 'fields' => pageFields('events', "What's Happening", 'Events & News', 'Stay updated with latest happenings at {site_name}', 'News & Events', 'Latest news, announcements and upcoming events from {site_name}: admissions updates, results, competitions and celebrations.')],
    'examination' => ['label' => 'Examination page', 'icon' => 'fa-file-lines', 'fields' => pageFields('examination', 'Academics', 'Examination', 'Datesheets & Board Results', 'Examination', 'Exam datesheets, schedules and board results for Matric and other classes at {site_name}. Check upcoming exams and past results.')],
    'alumni' => ['label' => 'Alumni page', 'icon' => 'fa-user-graduate', 'fields' => array_merge(pageFields('alumni', 'Alumni Network', 'Our Alumni', 'Join our alumni community and share your success story', 'Alumni', 'Meet the alumni of {site_name}, read where our former students are today, share your own experience and join the alumni network.'), [cf('alumni_default_photo', 'Default alumni photo', 'image', 'images/site/alumni-default.png', 'Shown for every alumnus who has no photo of their own. A square picture works best. Each alumnus\'s own photo is changed under Alumni in the sidebar.')])],
    'downloads' => ['label' => 'Downloads page', 'icon' => 'fa-download', 'fields' => pageFields('downloads', 'Resources', 'Downloads', 'Important documents and files for students', 'Downloads', 'Download admission forms, school notices, syllabus and study material from {site_name}, all in one place for students and parents.')],
    'contact' => ['label' => 'Contact page', 'icon' => 'fa-envelope', 'fields' => array_merge(
        pageFields('contact', 'Get In Touch', 'Contact Us', "Get in touch with us — we're here to help!", 'Contact Us', 'Contact {site_name}. Find our address, office hours and location map, or send us a message.'),
        [cf('contact_map_query', 'Map location', 'text', 'The City Scholars School', 'Text searched on Google Maps for the embedded map (school name + area works well).')]
    )],
];

// Section headings and banner texts on the inner pages, added to their page's group
$sectionFields = [
    'about' => [
        cf('about_values_eyebrow', 'Core values: small label', 'text', 'Our Values'),
        cf('about_values_heading', 'Core values: heading', 'text', 'Our Core Values'),
        cf('about_values_subtitle', 'Core values: subtitle', 'text', 'The principles that guide everything we do'),
        cf('about_cta_heading', 'Bottom banner: heading', 'text', 'Join Our Educational Community'),
        cf('about_cta_text', 'Bottom banner: text', 'textarea', 'Discover how {site_name} can help you achieve your academic goals'),
    ],
    'admission' => [
        cf('adm_form_eyebrow', 'Form: small label', 'text', 'Apply Online'),
        cf('adm_form_heading', 'Form: heading', 'text', 'Admission Form'),
        cf('adm_process_eyebrow', 'Process: small label', 'text', 'How It Works'),
        cf('home_adm_docs', 'Documents to bring', 'lines', "Birth certificate or B-Form\nPrevious class result card (if applicable)\nPassport-size photographs\nParent/Guardian CNIC copy", 'One document per line. Leave empty to hide the box.'),
        cf('home_adm_docs_note', 'Documents note', 'text', "Our admissions office will confirm the exact documents for your child's class."),
        cf('adm_process_heading', 'Process: heading', 'text', 'Admission Process'),
        cf('adm_process_subtitle', 'Process: subtitle', 'text', 'Simple steps to join {site_name}'),
        cf('adm_fee_eyebrow', 'Fees: small label', 'text', 'Fee Structure'),
        cf('adm_fee_heading', 'Fees: heading', 'text', 'Fee Information'),
        cf('adm_fee_subtitle', 'Fees: subtitle', 'text', 'Fee information for the current session'),
        cf('adm_fee_note', 'Fees: note under the list', 'text', 'Fees can vary by class. Please contact the admissions office to confirm the current fee schedule.'),
        cf('adm_fee_empty', 'Fees: text when no fees are listed', 'text', 'The fee schedule for the current session is available from our admissions office.', 'Shown while no fee items are active (AdminCP -> Fee Items).'),
        cf('adm_cta_heading', 'Bottom banner: heading', 'text', 'Need Help with Admission?'),
        cf('adm_cta_text', 'Bottom banner: text', 'textarea', 'Our admission team is here to assist you'),
    ],
    'alumni' => [
        cf('alumni_list_eyebrow', 'Alumni list: small label', 'text', 'Success Stories'),
        cf('alumni_list_heading', 'Alumni list: heading', 'text', 'Our Alumni'),
        cf('alumni_reviews_eyebrow', 'Reviews: small label', 'text', 'Testimonials'),
        cf('alumni_reviews_heading', 'Reviews: heading', 'text', 'Alumni Reviews'),
        cf('alumni_form_eyebrow', 'Registration form: small label', 'text', 'Get Involved'),
        cf('alumni_form_heading', 'Registration form: heading', 'text', 'Alumni Registration Form'),
        cf('alumni_review_form_eyebrow', 'Review form: small label', 'text', 'Share Feedback'),
        cf('alumni_review_form_heading', 'Review form: heading', 'text', 'Submit Your Review'),
    ],
    'campuses' => [
        cf('campuses_cta_heading', 'Bottom banner: heading', 'text', 'Want to Learn More?'),
        cf('campuses_cta_text', 'Bottom banner: text', 'textarea', 'Get in touch with us for admissions and details about any of our campuses'),
    ],
    'contact' => [
        cf('contact_form_eyebrow', 'Form: small label', 'text', 'Contact Form'),
        cf('contact_form_heading', 'Form: heading', 'text', 'Send us a Message'),
        cf('contact_map_eyebrow', 'Map: small label', 'text', 'Our Location'),
        cf('contact_map_heading', 'Map: heading', 'text', 'Find Us Here'),
        cf('contact_faq_eyebrow', 'FAQs: small label', 'text', 'FAQs'),
        cf('contact_faq_heading', 'FAQs: heading', 'text', 'Frequently Asked Questions'),
        cf('contact_faq_subtitle', 'FAQs: subtitle', 'text', 'Quick answers to common questions'),
        cf('contact_hours', 'Working hours', 'lines', '', 'One line per day or group of days: Day | Time, e.g. "Monday - Friday | 8:00 AM - 2:00 PM". Write "Closed" as the time for days off. Leave empty to hide the table. The short office hours in the header and footer are set under Settings.'),
    ],
    'events' => [
        cf('events_upcoming_eyebrow', 'Upcoming events: small label', 'text', 'Calendar'),
        cf('events_upcoming_heading', 'Upcoming events: heading', 'text', 'Upcoming Events'),
        cf('events_upcoming_subtitle', 'Upcoming events: subtitle', 'text', 'Mark your calendar for these exciting events'),
        cf('events_news_eyebrow', 'News: small label', 'text', 'Newsroom'),
        cf('events_news_heading', 'News: heading', 'text', 'Latest News & Announcements'),
        cf('events_news_subtitle', 'News: subtitle', 'text', 'Recent updates from {site_name}'),
        cf('events_cta_heading', 'Bottom banner: heading', 'text', 'Stay Connected!'),
        cf('events_cta_text', 'Bottom banner: text', 'textarea', 'Follow us on social media to never miss an update'),
        cf('events_past_heading', 'Past events: heading', 'text', 'Recent Events'),
    ],
    'examination' => [
        cf('exam_datesheet_eyebrow', 'Datesheets: small label', 'text', 'Schedule'),
        cf('exam_datesheet_heading', 'Datesheets: heading', 'text', 'Exam Datesheets'),
        cf('exam_results_eyebrow', 'Results: small label', 'text', 'Results'),
        cf('exam_results_heading', 'Results: heading', 'text', 'Board Results'),
    ],
    'faculty' => [
        cf('faculty_list_eyebrow', 'Teachers list: small label', 'text', 'Meet Our Teachers'),
        cf('faculty_list_heading', 'Teachers list: heading', 'text', 'The People Behind Every Lesson'),
        cf('faculty_why_eyebrow', 'Why our faculty: small label', 'text', 'Our Teachers'),
        cf('faculty_why_heading', 'Why our faculty: heading', 'text', 'What We Value in Our Teachers'),
        cf('faculty_why_subtitle', 'Why our faculty: subtitle', 'text', 'The qualities we look for in everyone who teaches at our school'),
        cf('faculty_cta_heading', 'Bottom banner: heading', 'text', 'Learn With Us'),
        cf('faculty_cta_text', 'Bottom banner: text', 'textarea', 'Join {site_name} and experience quality teaching'),
    ],
    'gallery' => [
        cf('gallery_videos_eyebrow', 'Videos: small label', 'text', 'Watch'),
        cf('gallery_videos_heading', 'Videos: heading', 'text', 'School Videos'),
        cf('gallery_videos_subtitle', 'Videos: subtitle', 'text', 'Events, activities and celebrations at {site_name}'),
    ],
];
foreach ($sectionFields as $group => $fields) {
    $registry[$group]['fields'] = array_merge($registry[$group]['fields'], $fields);
}

return $registry;
