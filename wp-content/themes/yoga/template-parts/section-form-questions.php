<section class="section-form-questions section-form-questions_contacts" id="section-form-questions">
    <div class="container">
        <div class="row">
            <div class="form-questions">
                <div class="form-questions__main">
                    <div class="form-questions__main-text">
                        <h3><?php echo esc_html(get_field('contacts_title', 'option') ?: 'Мы на связи!'); ?></h3>
                        <p><?php echo esc_html(get_field('contacts_description', 'option') ?: 'Если у вас есть вопросы или нужна помощь, оставьте сообщение, мы вам ответим в ближайшее время.'); ?></p>
                    </div>
                    
                    <form action="#" class="form-questions__main-form contacts-form" method="post">
                        <?php wp_nonce_field('contacts_nonce', 'contacts_nonce_field'); ?>
                        
                        <input type="text" name="contacts_name" class="input" required 
                               placeholder="<?php echo esc_attr(get_field('contacts_placeholder_name', 'option') ?: 'Имя'); ?>">
                        
                        <input type="email" name="contacts_email" class="input" required 
                               placeholder="<?php echo esc_attr(get_field('contacts_placeholder_email', 'option') ?: 'E-mail'); ?>">
                        
                        <input type="tel" name="contacts_phone" class="input input_phone" required>
                        
                        <div class="form-questions-textarea">
                            <textarea name="contacts_message" placeholder="<?php echo esc_attr(get_field('contacts_placeholder_message', 'option') ?: 'Ваш вопрос'); ?>" required class="input"></textarea>
                            
                            <input type="submit" id="form-questions-submit" style="display: none;">
                            
                            <?php 
                            $btn_icon = get_field('contacts_btn_icon', 'option');
                            if ($btn_icon) : ?>
                                <label for="form-questions-submit" class="btn">
                                    <img src="<?php echo esc_url($btn_icon); ?>" alt="Отправить">
                                </label>
                            <?php else : ?>
                                <label for="form-questions-submit" class="btn">
                                    <span>→</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="contacts-social">
                <?php 
                $social_links = get_field('contacts_social_links', 'option');
                if ($social_links) : 
                    foreach ($social_links as $social) : ?>
                        <a href="<?php echo esc_url($social['social_url']); ?>" class="contacts-social__item" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo esc_url($social['social_icon']); ?>" alt="<?php echo esc_attr($social['social_alt']); ?>">
                        </a>
                    <?php endforeach; 
                endif; ?>
            </div>
        </div>
    </div>
</section>