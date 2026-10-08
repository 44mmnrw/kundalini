<?php
/**
 * Уведомление об обработке персональных данных для гостевых форм обратной связи.
 *
 * @package Yoga
 */
if (is_user_logged_in()) {
    return;
}

$personal_data_fallback = function_exists('get_field')
    ? trim((string) (get_field('personal_data_processing_link', 'option') ?: get_field('personal_data_link', 'option')))
    : '';
if ($personal_data_fallback === '') {
    $personal_data_fallback = function_exists('yoga_get_privacy_policy_url')
        ? yoga_get_privacy_policy_url()
        : home_url('/privacy/');
}
$personal_data_url = function_exists('yoga_get_legal_document_url')
    ? yoga_get_legal_document_url('personal_data', $personal_data_fallback)
    : $personal_data_fallback;
?>
<p class="form-questions__personal-data-notice">
    Отправляя сообщение, вы даёте согласие на
    <a href="<?php echo esc_url($personal_data_url); ?>" target="_blank" rel="noopener noreferrer">обработку персональных данных</a>.
</p>
