<?php
require_once 'includes/config.php';
$page_title = content('seo_faculty_title');
$meta_description = content('seo_faculty_description');
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('faculty'); ?>

<!-- Faculty Section -->
<section class="bg-light">
    <div class="container">
        <h2 class="sr-only">Our teachers</h2>
        <div class="card-grid">
            <?php
            // Fetch faculty from database
            $faculty_query = "SELECT DISTINCT * FROM faculty WHERE status = 'active' ORDER BY display_order ASC";
            $faculty_result = mysqli_query($conn, $faculty_query);

            if ($faculty_result && mysqli_num_rows($faculty_result) > 0) {
                while ($teacher = mysqli_fetch_assoc($faculty_result)) {
                    echo '<div class="card" style="text-align: center;">';
                    if (!empty($teacher['photo'])) {
                        echo '<img src="' . htmlspecialchars($teacher['photo']) . '" alt="' . htmlspecialchars($teacher['name']) . '" style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 20px; object-fit: cover; box-shadow: var(--shadow-sm); border: 4px solid var(--surface); outline: 1px solid var(--border-color);">';
                    } else {
                        echo '<div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px; box-shadow: var(--shadow-sm);"><i class="fas fa-user"></i></div>';
                    }
                    echo '<h3>' . htmlspecialchars($teacher['name']) . '</h3>';
                    echo '<p style="color: var(--accent-color); font-weight: 600; margin-bottom: 15px;">' . htmlspecialchars($teacher['designation']) . '</p>';
                    if (!empty($teacher['subjects'])) {
                        echo '<p style="margin-bottom: 10px;"><strong>Subjects:</strong> ' . htmlspecialchars($teacher['subjects']) . '</p>';
                    }
                    if (!empty($teacher['qualification'])) {
                        echo '<p style="margin-bottom: 10px;"><strong>Qualification:</strong> ' . htmlspecialchars($teacher['qualification']) . '</p>';
                    }
                    if (!empty($teacher['experience'])) {
                        echo '<p><strong>Experience:</strong> ' . htmlspecialchars($teacher['experience']) . ' years</p>';
                    }
                    echo '</div>';
                }
            } else {
                // Default faculty members
                $default_faculty = [
                    [
                        'name' => 'Prof. Muhammad Ahmed',
                        'designation' => 'Head of Science Department',
                        'subjects' => 'Physics, Mathematics',
                        'qualification' => 'MSc Physics',
                        'experience' => '15'
                    ],
                    [
                        'name' => 'Dr. Fatima Khan',
                        'designation' => 'Senior Biology Teacher',
                        'subjects' => 'Biology, Chemistry',
                        'qualification' => 'PhD Biology',
                        'experience' => '12'
                    ],
                    [
                        'name' => 'Sir Usman Ali',
                        'designation' => 'Mathematics Expert',
                        'subjects' => 'Mathematics, Statistics',
                        'qualification' => 'MSc Mathematics',
                        'experience' => '10'
                    ],
                    [
                        'name' => 'Miss Ayesha Malik',
                        'designation' => 'English Language Instructor',
                        'subjects' => 'English, IELTS',
                        'qualification' => 'MA English',
                        'experience' => '8'
                    ],
                    [
                        'name' => 'Sir Hassan Raza',
                        'designation' => 'Computer Science Teacher',
                        'subjects' => 'Programming, Web Development',
                        'qualification' => 'BS Computer Science',
                        'experience' => '7'
                    ],
                    [
                        'name' => 'Sir Bilal Iqbal',
                        'designation' => 'Chemistry Specialist',
                        'subjects' => 'Chemistry',
                        'qualification' => 'MSc Chemistry',
                        'experience' => '11'
                    ],
                    [
                        'name' => 'Miss Zainab Shah',
                        'designation' => 'Urdu & Islamiat Teacher',
                        'subjects' => 'Urdu, Islamiat, Pakistan Studies',
                        'qualification' => 'MA Urdu, MA Islamiat',
                        'experience' => '9'
                    ],
                    [
                        'name' => 'Sir Kamran Abbas',
                        'designation' => 'Entry Test Coordinator',
                        'subjects' => 'ECAT, MDCAT Preparation',
                        'qualification' => 'MSc Physics',
                        'experience' => '13'
                    ]
                ];

                foreach ($default_faculty as $teacher) {
                    echo '<div class="card" style="text-align: center;">';
                    echo '<div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px; box-shadow: var(--shadow-sm);"><i class="fas fa-chalkboard-teacher"></i></div>';
                    echo '<h3>' . htmlspecialchars($teacher['name']) . '</h3>';
                    echo '<p style="color: var(--accent-color); font-weight: 600; margin-bottom: 15px;">' . htmlspecialchars($teacher['designation']) . '</p>';
                    echo '<p style="margin-bottom: 10px;"><strong>Subjects:</strong> ' . htmlspecialchars($teacher['subjects']) . '</p>';
                    echo '<p style="margin-bottom: 10px;"><strong>Qualification:</strong> ' . htmlspecialchars($teacher['qualification']) . '</p>';
                    echo '<p><strong>Experience:</strong> ' . htmlspecialchars($teacher['experience']) . ' years</p>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- Why Our Faculty is Best -->
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('faculty_why_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('faculty_why_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('faculty_why_subtitle')); ?></p>
        </div>
        <div class="card-grid">
            <?php renderFeatureCardGrid('faculty_highlights'); ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section>
    <div class="container">
        <div class="cta-banner reveal">
            <h2><?php echo htmlspecialchars(content('faculty_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('faculty_cta_text')); ?></p>
            <div class="btn-group" style="justify-content: center;">
                <a href="admission" class="btn btn-secondary">Apply for Admission</a>
                <a href="courses" class="btn btn-outline">View Courses</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
