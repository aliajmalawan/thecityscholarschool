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
        cf('site_logo', 'School logo', 'logo', 'images/tameer-e-nau-logo.png', 'Square or round logo (PNG/JPG/WebP, up to 8 MB; resized and compressed automatically). Used in the header, footer, favicon area and share previews.'),
        cf('site_tagline', 'Tagline under the school name', 'text', 'Canal View, Hasilpur', 'Small line below the name in the header and footer.'),
        cf('seo_title_suffix', 'Home page title suffix', 'text', 'Canal View Hasilpur', 'Home page browser title becomes "School name | this text".'),
        cf('apply_button_label', 'Header "Apply" button text', 'text', 'Apply Online'),
        cf('seo_home_description', 'Home page Google description', 'textarea', '{site_name} in Canal View, Hasilpur offers quality education with caring teachers, a disciplined environment and a focus on character.'),
    ]],

    'home_hero' => ['label' => 'Home: Hero & About', 'icon' => 'fa-house', 'fields' => [
        cf('home_hero_eyebrow', 'Hero small label', 'text', 'Canal View, Hasilpur'),
        cf('hero_title', 'Hero headline', 'text', 'Building Strong Foundations for a Brighter Future'),
        cf('hero_description', 'Hero text', 'textarea', 'At {site_name}, we are committed to providing quality education, character development and a nurturing environment where every student can learn, grow and succeed.'),
        cf('hero_image', 'Fallback hero photo', 'image', '', 'Only used when there are no active slides. The home slider itself is managed under Home Slider in the sidebar.'),
        cf('home_hero_btn1_label', 'Hero button 1 text', 'text', 'Explore Our School'),
        cf('home_hero_btn1_link', 'Hero button 1 link', 'url', 'about'),
        cf('home_hero_btn2_label', 'Hero button 2 text', 'text', 'Contact Us'),
        cf('home_hero_btn2_link', 'Hero button 2 link', 'url', 'contact'),
        cf('home_about_eyebrow', 'About section small label', 'text', 'About Us'),
        cf('home_about_heading', 'About section heading', 'text', 'Welcome to {site_name}'),
        cf('home_about_text', 'About section introduction', 'textarea', 'Located in Canal View, Hasilpur, our school is built on the belief that every child deserves a strong start. We combine a disciplined, supportive academic environment with caring teachers, so students grow in knowledge, confidence and character and are ready to contribute positively to their community.'),
        cf('home_about_image', 'About section photo', 'image', 'images/site/about-home.jpg', 'Portrait photo (4:5) looks best.'),
        cf('home_about_btn_label', 'About section button text', 'text', 'Learn More'),
        cf('stats_label_students', 'Statistics label: students', 'text', 'Students', 'Numbers are edited in Settings -> Statistics.'),
        cf('stats_label_faculty', 'Statistics label: faculty', 'text', 'Faculty Members'),
        cf('stats_label_years', 'Statistics label: years', 'text', 'Years of Excellence'),
        cf('stats_label_programs', 'Statistics label: programs', 'text', 'Programs'),
    ]],

    'home_sections' => ['label' => 'Home: Sections', 'icon' => 'fa-table-cells-large', 'fields' => [
        cf('home_quick_1_title', 'Quick link 1: title', 'text', 'Admissions', 'Quick links under the slider. Clear the title to hide a card.'),
        cf('home_quick_1_text', 'Quick link 1: text', 'text', 'Apply online in a few simple steps'),
        cf('home_quick_1_link', 'Quick link 1: link', 'url', 'admission'),
        cf('home_quick_2_title', 'Quick link 2: title', 'text', 'Academics'),
        cf('home_quick_2_text', 'Quick link 2: text', 'text', 'Our programs, stage by stage'),
        cf('home_quick_2_link', 'Quick link 2: link', 'url', 'courses'),
        cf('home_quick_3_title', 'Quick link 3: title', 'text', 'Campus Life'),
        cf('home_quick_3_text', 'Quick link 3: text', 'text', 'Photos from our classrooms and events'),
        cf('home_quick_3_link', 'Quick link 3: link', 'url', 'gallery'),
        cf('home_quick_4_title', 'Quick link 4: title', 'text', 'Contact Us'),
        cf('home_quick_4_text', 'Quick link 4: text', 'text', 'Visit us or send a message'),
        cf('home_quick_4_link', 'Quick link 4: link', 'url', 'contact'),
        cf('home_academics_eyebrow', 'Academics: small label', 'text', 'Academics'),
        cf('home_academics_heading', 'Academics: heading', 'text', 'Learning at Every Stage'),
        cf('home_academics_subtitle', 'Academics: subtitle', 'text', "A clear path from a child's first years to secondary school"),
        cf('academics_1_title', 'Academics card 1: title', 'text', 'Early Years'),
        cf('academics_1_desc', 'Academics card 1: text', 'textarea', 'A warm and caring start where young learners build curiosity, early language and social skills.'),
        cf('academics_2_title', 'Academics card 2: title', 'text', 'Primary Education'),
        cf('academics_2_desc', 'Academics card 2: text', 'textarea', 'Strong foundations in literacy, numeracy and values, taught with patience and personal attention.'),
        cf('academics_3_title', 'Academics card 3: title', 'text', 'Middle School'),
        cf('academics_3_desc', 'Academics card 3: text', 'textarea', 'Building confidence, critical thinking and good study habits as students take on broader subjects.'),
        cf('academics_4_title', 'Academics card 4: title', 'text', 'Secondary Education'),
        cf('academics_4_desc', 'Academics card 4: text', 'textarea', 'Focused preparation for Matric (9th & 10th) in Science, Arts and Computer Science groups.'),
        cf('home_facilities_eyebrow', 'Facilities: small label', 'text', 'Campus & Facilities', 'The facility cards themselves are in Feature Cards -> Facilities.'),
        cf('home_facilities_heading', 'Facilities: heading', 'text', 'Our Facilities'),
        cf('home_facilities_subtitle', 'Facilities: subtitle', 'text', 'Spaces and activities that support learning beyond the textbook'),
        cf('home_philosophy_eyebrow', 'Philosophy: small label', 'text', 'Our Philosophy'),
        cf('home_philosophy_heading', 'Philosophy: heading', 'text', 'Where Learning is a Personal Journey'),
        cf('home_philosophy_text', 'Philosophy: paragraph', 'textarea', "At {site_name}, we believe every child learns differently. Our teachers take the time to understand each student's strengths, curiosities, and pace — building a supportive environment where confidence grows alongside knowledge, and every learner is guided toward their own version of success."),
        cf('home_assessment_eyebrow', 'Assessment: small label', 'text', 'Assessment Philosophy'),
        cf('home_assessment_heading', 'Assessment: heading', 'text', 'Our Approach to Assessment'),
        cf('assessment_paragraph_1', 'Assessment: paragraph 1', 'textarea', 'We believe assessment should guide learning, not just measure it. Our teachers use continuous, classroom-based assessment - class tests, assignments, and regular feedback - alongside formal examinations to build a complete picture of every student\'s progress.'),
        cf('assessment_paragraph_2', 'Assessment: paragraph 2', 'textarea', 'This approach helps us identify each student\'s strengths early, address gaps before they widen, and keep parents informed through regular progress updates rather than a single end-of-term result.'),
        cf('home_attributes_eyebrow', 'Learner attributes: small label', 'text', 'What We Nurture'),
        cf('home_attributes_heading', 'Learner attributes: heading', 'text', 'Learner Attributes We Build'),
        cf('home_attributes_subtitle', 'Learner attributes: subtitle', 'text', 'Qualities every student develops throughout their journey with us'),
        cf('home_digital_eyebrow', 'Digital campus: small label', 'text', 'Digitized Campus'),
        cf('home_digital_heading', 'Digital campus: heading', 'text', 'Complete Digital School Management System'),
        cf('home_digital_subtitle', 'Digital campus: subtitle', 'text', '{site_name} is fully digitized — experience modern education management with cutting-edge technology.'),
        cf('home_why_eyebrow', 'Why choose us: small label', 'text', 'Why Choose Us'),
        cf('home_why_heading', 'Why choose us: heading', 'text', 'Why Choose Tameer-e-Nau?'),
        cf('home_why_subtitle', 'Why choose us: subtitle', 'text', 'Quality education, strong values and a caring environment for every student'),
        cf('home_life_eyebrow', 'Campus life: small label', 'text', 'Campus Life', 'The photo cards come from Gallery categories automatically.'),
        cf('home_life_heading', 'Campus life: heading', 'text', 'Life at {site_name}'),
        cf('home_life_subtitle', 'Campus life: subtitle', 'text', 'Moments from our classrooms, morning assemblies and learning activities'),
        cf('home_testimonials_eyebrow', 'Testimonials: small label', 'text', 'Testimonials'),
        cf('home_testimonials_heading', 'Testimonials: heading', 'text', 'What Our Community Says'),
        cf('home_testimonials_subtitle', 'Testimonials: subtitle', 'text', 'Real experiences shared by our school community'),
        cf('home_news_eyebrow', 'News & events: small label', 'text', 'Stay Updated'),
        cf('home_news_heading', 'News & events: heading', 'text', 'News & Events'),
        cf('home_news_subtitle', 'News & events: subtitle', 'text', 'The latest announcements and upcoming activities from {site_name}'),
        cf('home_faq_eyebrow', 'FAQs: small label', 'text', 'Got Questions?'),
        cf('home_faq_heading', 'FAQs: heading', 'text', 'Frequently Asked Questions'),
        cf('home_faq_subtitle', 'FAQs: subtitle', 'text', 'Quick answers for prospective families'),
        cf('home_contact_eyebrow', 'Contact block: small label', 'text', 'Get In Touch'),
        cf('home_contact_heading', 'Contact block: heading', 'text', 'Have a Question? Send Us a Message'),
        cf('home_contact_text', 'Contact block: text', 'textarea', 'Our admission team typically responds within 24-48 hours. For urgent queries, feel free to call or visit our campus directly.'),
    ]],

    'home_admissions' => ['label' => 'Home: Admissions & Closing', 'icon' => 'fa-user-plus', 'fields' => [
        cf('home_adm_eyebrow', 'Admissions: small label', 'text', 'Admissions'),
        cf('home_adm_heading', 'Admissions: heading', 'text', "Start Your Child's Educational Journey"),
        cf('home_adm_text', 'Admissions: introduction', 'textarea', 'Joining {site_name} is simple. Complete the online application, bring the required documents to our office, and our team will guide you through the rest.'),
        cf('home_adm_btn1_label', 'Admissions: button 1 text', 'text', 'Apply Now'),
        cf('home_adm_btn2_label', 'Admissions: button 2 text', 'text', 'Contact Admissions'),
        cf('home_adm_docs', 'Required documents', 'lines', "Birth certificate or B-Form\nPrevious class result card (if applicable)\nPassport-size photographs\nParent/Guardian CNIC copy", 'One document per line.'),
        cf('home_adm_docs_note', 'Documents note', 'text', "Our admissions office will confirm the exact documents for your child's class."),
        cf('home_cta_heading', 'Closing banner: heading', 'text', 'Give Your Child a Strong Foundation for the Future'),
        cf('home_cta_text', 'Closing banner: text', 'textarea', 'Discover an environment where education, character and opportunity come together.'),
        cf('home_cta_btn1_label', 'Closing banner: button 1 text', 'text', 'Contact Us'),
        cf('home_cta_btn2_label', 'Closing banner: button 2 text', 'text', 'Apply Now'),
    ]],

    'about' => ['label' => 'About page', 'icon' => 'fa-circle-info', 'fields' => array_merge(
        pageFields('about', 'About Us', 'About {site_name}', 'Learn about our mission, vision, and commitment to educational excellence', 'About Us', 'Learn about {site_name} in Hasilpur: our mission, vision and core values, and our commitment to quality education and student development.'),
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
        pageFields('courses', 'Academics', 'Our Courses', 'A complete path of study from Early Years to Matric', 'Courses & Academics', 'Explore the courses at {site_name}, Canal View Hasilpur: Early Years, Primary, Middle School and Matric (Science, Computer Science and Arts groups).'),
        [
            cf('courses_path_eyebrow', 'Overview: small label', 'text', 'Academic Path', 'The courses themselves are added and edited under Courses in the sidebar.'),
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
            cf('courses_cta_heading', 'Bottom banner: heading', 'text', 'Ready to Enroll?'),
            cf('courses_cta_text', 'Bottom banner: text', 'text', 'Start your learning journey with {site_name} today'),
        ]
    )],
    'admission' => ['label' => 'Admission page', 'icon' => 'fa-file-pen', 'fields' => pageFields('admission', 'Join Us', 'Admission Application', 'Start your educational journey with {site_name}', 'Admissions', 'Apply online to {site_name}, Canal View Hasilpur. See the admission process, the documents you need and how to reach our admissions office.')],
    'faculty' => ['label' => 'Faculty page', 'icon' => 'fa-chalkboard-user', 'fields' => pageFields('faculty', 'Our Team', 'Our Expert Faculty', 'Meet our dedicated and experienced teachers committed to your success', 'Our Faculty', 'Meet the teachers of {site_name} in Hasilpur, dedicated to helping every student learn, grow and succeed.')],
    'campuses' => ['label' => 'Campus page', 'icon' => 'fa-school', 'fields' => pageFields('campuses', 'Our Network', 'Our Campuses', 'Beyond our main campus, {site_name} operates specialized institutes for focused learning', 'Our Campuses', 'Explore the campuses of {site_name}: our main school at Canal View, Hasilpur and our specialised institutes for computer studies and sports training.')],
    'gallery' => ['label' => 'Gallery page', 'icon' => 'fa-images', 'fields' => pageFields('gallery', 'Campus Life', 'Photo Gallery', 'Explore moments captured at {site_name}', 'Photo Gallery', 'Photos and videos of classroom activities, school events and celebrations at {site_name}, Canal View Hasilpur. See everyday campus life for yourself.')],
    'events' => ['label' => 'News & Events page', 'icon' => 'fa-calendar-days', 'fields' => pageFields('events', "What's Happening", 'Events & News', 'Stay updated with latest happenings at {site_name}', 'News & Events', 'Latest news, announcements and upcoming events from {site_name} in Canal View, Hasilpur: admissions updates, results, competitions and celebrations.')],
    'examination' => ['label' => 'Examination page', 'icon' => 'fa-file-lines', 'fields' => pageFields('examination', 'Academics', 'Examination', 'Datesheets & Board Results', 'Examination', 'Exam datesheets, schedules and board results for Matric and other classes at {site_name}, Canal View Hasilpur. Check upcoming exams and past results.')],
    'alumni' => ['label' => 'Alumni page', 'icon' => 'fa-user-graduate', 'fields' => array_merge(pageFields('alumni', 'Alumni Network', 'Our Alumni', 'Join our alumni community and share your success story', 'Alumni', 'Meet the alumni of {site_name} in Hasilpur, read where our former students are today, share your own experience and join the alumni network.'), [cf('alumni_default_photo', 'Default alumni photo', 'image', 'images/site/alumni-default.png', 'Shown for every alumnus who has no photo of their own. A square picture works best. Each alumnus\'s own photo is changed under Alumni in the sidebar.')])],
    'downloads' => ['label' => 'Downloads page', 'icon' => 'fa-download', 'fields' => pageFields('downloads', 'Resources', 'Downloads', 'Important documents and files for students', 'Downloads', 'Download admission forms, school notices, syllabus and study material from {site_name}, Canal View Hasilpur, all in one place for students and parents.')],
    'contact' => ['label' => 'Contact page', 'icon' => 'fa-envelope', 'fields' => array_merge(
        pageFields('contact', 'Get In Touch', 'Contact Us', "Get in touch with us — we're here to help!", 'Contact Us', 'Contact {site_name} at Canal View, Hasilpur. Find our address, office hours and location map, or send us a message.'),
        [cf('contact_map_query', 'Map location', 'text', 'Tameer-e-Nau Public School Canal View Hasilpur', 'Text searched on Google Maps for the embedded map (school name + area works well).')]
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
        cf('adm_process_heading', 'Process: heading', 'text', 'Admission Process'),
        cf('adm_process_subtitle', 'Process: subtitle', 'text', 'Simple steps to join {site_name}'),
        cf('adm_fee_eyebrow', 'Fees: small label', 'text', 'Fee Structure'),
        cf('adm_fee_heading', 'Fees: heading', 'text', 'Fee Information'),
        cf('adm_fee_subtitle', 'Fees: subtitle', 'text', 'Transparent and affordable fee structure'),
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
        cf('contact_hours', 'Working hours', 'lines', "Monday - Friday | 8:00 AM - 5:00 PM\nSaturday | 9:00 AM - 3:00 PM\nSunday | Closed", 'One line per day or group of days: Day | Time. Write "Closed" as the time for days off. The short office hours in the footer are set under Settings.'),
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
    ],
    'examination' => [
        cf('exam_datesheet_eyebrow', 'Datesheets: small label', 'text', 'Schedule'),
        cf('exam_datesheet_heading', 'Datesheets: heading', 'text', 'Exam Datesheets'),
        cf('exam_results_eyebrow', 'Results: small label', 'text', 'Results'),
        cf('exam_results_heading', 'Results: heading', 'text', 'Board Results'),
    ],
    'faculty' => [
        cf('faculty_why_eyebrow', 'Why our faculty: small label', 'text', 'What Sets Us Apart'),
        cf('faculty_why_heading', 'Why our faculty: heading', 'text', 'Why Our Faculty Stands Out'),
        cf('faculty_why_subtitle', 'Why our faculty: subtitle', 'text', 'Qualities that make our teachers exceptional'),
        cf('faculty_cta_heading', 'Bottom banner: heading', 'text', 'Learn from the Best!'),
        cf('faculty_cta_text', 'Bottom banner: text', 'textarea', 'Join {site_name} and experience quality teaching'),
    ],
    'gallery' => [
        cf('gallery_videos_eyebrow', 'Videos: small label', 'text', 'Watch'),
        cf('gallery_videos_heading', 'Videos: heading', 'text', 'School Videos'),
        cf('gallery_videos_subtitle', 'Videos: subtitle', 'text', 'Events, activities and celebrations at {site_name}'),
    ],
    'home_sections' => [
        cf('home_campuses_eyebrow', 'Campuses: small label', 'text', 'Our Network'),
        cf('home_campuses_heading', 'Campuses: heading', 'text', 'Our Campuses'),
        cf('home_campuses_subtitle', 'Campuses: subtitle', 'text', 'Beyond our main campus, {site_name} runs specialised institutes for focused learning'),
    ],
];
foreach ($sectionFields as $group => $fields) {
    $registry[$group]['fields'] = array_merge($registry[$group]['fields'], $fields);
}

return $registry;
