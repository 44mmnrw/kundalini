<section class="section-questions" id="section-questions">
    <div class="container">
        <div class="row">
            <div class="questions-main">
                <h2 class="wow flipInX delay-200ms">
                    <?php echo esc_html(get_field('faq_title', 'option') ?: 'ВОПРОС-ОТВЕТ'); ?>
                </h2>
            </div>    
        </div>
        <div class="row">
            <div class="questions">
                <div class="questions-intro wow fadeIn delay-200ms">
                    <div class="questions-quote">
                        <div class="questions-quote__decor wow fadeInDown delay-600ms">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/questions-quote-decor.png" alt="Декоративный элемент">
                        </div>
                        <b>
                            <?php echo esc_html(get_field('faq_quote', 'option') ?: 'Йога – это искусство слушать свое тело, ум и душу, находя гармонию между ними.'); ?>
                        </b>
                    </div>
                </div>
                
                <div class="questions-answers">
                    <div class="questions-items">
                        <?php
                        $faq_items = get_field('faq_items', 'option');
                        if ($faq_items) :
                            $counter = 1;
                            foreach ($faq_items as $index => $item) :
                                $question = $item['faq_question'];
                                $answer = $item['faq_answer'];
                                ?>
                                <div class="question wow fadeIn delay-200ms" data-delay="<?php echo ($index * 100) + 200; ?>ms">
                                    <div class="question__main">
                                        <span class="question__main-text">
                                            <span><?php echo str_pad($counter, 2, '0', STR_PAD_LEFT); ?>.</span>
                                            <?php echo esc_html($question); ?>
                                        </span>
                                        <div class="question-icon">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/slick-arrow-next.png" alt="Раскрыть ответ" class="active">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/slick-arrow-next_active.png" alt="Скрыть ответ">
                                        </div>
                                    </div>
                                    <div class="question__sub">
                                        <?php echo apply_filters('the_content', $answer); ?>
                                    </div>
                                </div>
                                <?php
                                $counter++;
                            endforeach;
                        else :
                            // Fallback если нет вопросов в ACF
                            $default_questions = array(
                                'Какой тариф мне выбрать, если я только начинаю заниматься йогой?',
                                'Что входит в пробный период и чем он ограничен?',
                                'Есть ли расписание занятий, или я могу заниматься в любое время?',
                                'Могу ли я сменить тариф после оформления подписки?',
                                'Можно ли общаться с учителем и задавать вопросы?',
                                'Как отменить подписку, если я решу больше не пользоваться платформой?'
                            );
                            
                            $default_answers = array(
                                'Если вы новичок, рекомендуем начать с Базового тарифа – он включает все необходимые текстовые материалы для самостоятельной практики. Если хотите лучше понимать философию йоги, выбирайте тариф «Углублённый».',
                                'Пробный период дает доступ ко всем основным функциям платформы на ограниченное время. Вы можете ознакомиться с интерфейсом, попробовать несколько практик и оценить удобство использования.',
                                'Вы можете заниматься в любое удобное для вас время. Все материалы доступны 24/7, вы сами планируете свой график занятий.',
                                'Да, вы можете сменить тариф в любое время в личном кабинете. Изменения вступят в силу с начала следующего платежного периода.',
                                'Да, на тарифе «Премиум» доступна возможность задавать вопросы учителю через специальную форму в личном кабинете.',
                                'Вы можете отменить подписку в любое время в разделе "Настройки аккаунта". После отмены доступ к платным материалам сохранится до конца оплаченного периода.'
                            );
                            
                            foreach ($default_questions as $index => $question) :
                                ?>
                                <div class="question wow fadeIn delay-200ms" data-delay="<?php echo ($index * 100) + 200; ?>ms">
                                    <div class="question__main">
                                        <span class="question__main-text">
                                            <span><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>.</span>
                                            <?php echo esc_html($question); ?>
                                        </span>
                                        <div class="question-icon">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/slick-arrow-next.png" alt="Раскрыть ответ" class="active">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/slick-arrow-next_active.png" alt="Скрыть ответ">
                                        </div>
                                    </div>
                                    <div class="question__sub">
                                        <p><?php echo esc_html($default_answers[$index] ?? ''); ?></p>
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                    
                    <a href="<?php echo esc_html(get_field('questions_more_link') ?: '/faq/'); ?>" class="questions-answers__more wow fadeIn delay-200ms">
                        <span>Ещё</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>