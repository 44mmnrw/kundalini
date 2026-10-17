<?php
/** Кабинет: отзывы. Figma 2820:30353, 2873:33194, 2994:29454. */
$user_id = (int) ($args['user_id'] ?? get_current_user_id());
$reviews = yoga_get_account_reviews($user_id);
$editable_review = null;
?>
<div class="lk-community lk-reviews">
	<header class="lk-community__head"><h1>Мои отзывы</h1><p>Поделитесь впечатлениями о практиках и получите скидки на подписку</p></header>
	<div class="lk-review-bonus">
		<div class="lk-review-bonus__copy">
			<span class="lk-review-bonus__badge"><?php yoga_lk_section_icon('review-badge-star'); ?>Бонусная программа</span>
			<div>
				<h2><?php echo $reviews ? 'Спасибо, вы уже оставили отзыв!' : 'Вы еще не оставляли отзывы, загрузите фото и оставьте отзыв, а мы подарим вам промокод на скидку!'; ?></h2>
				<?php if ($reviews) : ?>
				<p><strong>Внимание:</strong> Если вы оставили отзыв со своей фотографией и заполнили все поля, после модерации отзыва, вы найдете промокод на скидку в разделе <a href="<?php echo esc_url(yoga_get_lk_section_url('promocodes')); ?>">промокоды</a> или на электронной почте, привязанной к вашему профилю</p>
				<?php else : ?>
				<p><strong>Внимание:</strong> чтобы получить промокод, необходимо при отправке отзыва загрузить своё фото и заполнить все поля.</p>
				<?php endif; ?>
			</div>
			<strong class="lk-review-bonus__note">Без фото промокод не выдаётся</strong>
		</div>
		<div class="lk-review-bonus__gift" aria-hidden="true"><span>%</span><?php yoga_lk_section_icon('review-gift'); ?></div>
	</div>
	<section class="lk-community__section" aria-labelledby="lk-reviews-title">
		<h2 class="lk-community__section-title" id="lk-reviews-title"><?php yoga_lk_section_icon('review-comment'); ?>Ваши отзывы (<?php echo count($reviews); ?>)</h2>
		<?php if (!$reviews) : ?>
		<div class="lk-reviews-empty">
			<span class="lk-reviews-empty__icon"><?php yoga_lk_section_icon('review-star'); ?></span>
			<div><h3>Вы ещё не оставляли отзывы</h3><p>Поделитесь впечатлением от нашей платформы! Если прикрепите к отзыву своё фото, мы подарим вам промокод со скидкой.</p></div>
			<button type="button" class="lk-community__purple-button" data-open-review>Добавить отзыв</button>
		</div>
		<?php else : foreach ($reviews as $review) :
			$job = (string) get_post_meta($review->ID, '_yoga_review_job', true);
			$age = (int) get_post_meta($review->ID, '_yoga_review_age', true);
			$state = $review->post_status === 'publish' ? 'approved' : ($review->post_status === 'draft' ? 'denied' : 'pending');
			if ($state === 'denied') { $editable_review = $review; }
		?>
		<article class="lk-account-review">
			<header>
				<?php if (has_post_thumbnail($review->ID)) : ?><?php echo get_the_post_thumbnail($review->ID, 'thumbnail', array('class' => 'lk-account-review__photo', 'alt' => '')); ?><?php else : ?><span class="lk-account-review__photo lk-account-review__photo--empty"><?php yoga_lk_section_icon('review-upload'); ?></span><?php endif; ?>
				<div class="lk-account-review__author"><h3><?php echo esc_html($review->post_title); ?><?php if ($age) : ?>, <?php echo esc_html(yoga_lk_review_age($age)); ?><?php endif; ?></h3><p><?php echo esc_html(($job ? $job . ' • ' : '') . get_the_date('d.m.Y', $review)); ?></p></div>
				<span class="lk-account-review__status lk-account-review__status--<?php echo $state; ?>"><?php yoga_lk_section_icon('review-' . $state); ?><?php echo array('approved' => 'Опубликован', 'denied' => 'Отклонён', 'pending' => 'На модерации')[$state]; ?></span>
				<?php if ($state === 'approved' && !has_post_thumbnail($review->ID)) : ?><span class="lk-account-review__reward">Без промокода, нет фото</span><?php elseif ($state === 'approved' && get_post_meta($review->ID, '_yoga_review_coupon_id', true)) : ?><span class="lk-account-review__reward">Промокод получен</span><?php endif; ?>
			</header>
			<div class="lk-account-review__text"><?php echo nl2br(esc_html($review->post_content)); ?></div>
			<?php if ($state === 'denied') : ?><button type="button" class="lk-account-review__edit" data-open-review><?php yoga_lk_section_icon('review-edit'); ?>Редактировать</button><?php endif; ?>
		</article>
		<?php endforeach; endif; ?>
	</section>
</div>
<?php if (!$reviews || $editable_review) { get_template_part('template-parts/lk-review-form', null, array('user_id' => $user_id, 'review' => $editable_review)); } ?>
