<?php if (empty(getLeadershipMessages())) { return; } ?>
<!-- Leadership Messages Section -->
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Leadership</span>
            <h2>Message from Our Leadership</h2>
            <p>Words of wisdom from our esteemed leaders</p>
        </div>

        <?php
        $leaders = getLeadershipMessages();
        $count = 0;
        foreach ($leaders as $leader):
            $count++;
            // Every second message puts the photo on the right (desktop only; on small screens the photo always comes first)
            $is_reverse = ($count % 2 == 0);
        ?>

        <!-- Leadership Message: <?php echo htmlspecialchars($leader['name']); ?> -->
        <div class="leader-card reveal<?php echo $is_reverse ? ' leader-card-reverse' : ''; ?>">
            <div class="leader-profile">
                <div class="leader-photo">
                    <?php if (!empty($leader['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($leader['photo']); ?>" alt="<?php echo htmlspecialchars($leader['name']); ?>" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                </div>
                <h3><?php echo htmlspecialchars($leader['role_title'] ?? 'Leadership Message'); ?></h3>
                <p class="leader-name"><?php echo htmlspecialchars($leader['name']); ?></p>
                <p class="leader-designation"><?php echo htmlspecialchars($leader['designation']); ?></p>
            </div>

            <div class="leader-message">
                <i class="fas fa-quote-left leader-quote" aria-hidden="true"></i>
                <div class="leader-text"><?php echo nl2br(htmlspecialchars($leader['message'])); ?></div>
                <div class="leader-sign">
                    <?php if (!empty($leader['signature'])): ?>
                        <img src="<?php echo htmlspecialchars($leader['signature']); ?>" alt="Signature" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <p class="leader-sign-name"><?php echo htmlspecialchars($leader['name']); ?></p>
                    <p class="leader-designation"><?php echo htmlspecialchars($leader['designation']); ?></p>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
</section>
