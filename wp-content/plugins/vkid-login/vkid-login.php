<?php
/**
 * Plugin Name: VK ID Login (Custom)
 * Description: Вход/регистрация в WordPress через VK ID Web SDK (PKCE): кнопка, обмен кода на токен, создание/логин пользователя.
 * Version: 0.4.0 
 */

if (!defined('ABSPATH')) exit;

class VKID_Login_Plugin {
  const OPTION_APP_ID         = 'vkid_app_id';
  const OPTION_CLIENT_SECRET  = 'vkid_client_secret'; // для refresh потока; на обмен кода не обязателен
  const OPTION_REDIRECT_URL   = 'vkid_redirect_url';  // ДОЛЖЕН совпадать 1:1 с VK ID (без фрагмента #)
  const OPTION_SCOPE          = 'vkid_scope';         // напр.: "email" или "email,offline"

  public function __construct() {
    add_shortcode('vkid_login', [$this, 'shortcode']);
    add_action('rest_api_init', [$this, 'register_routes']);
    add_action('admin_menu',   [$this, 'admin_menu']);
    add_action('admin_init',   [$this, 'register_settings']);
    add_action('wp_footer',    [$this, 'render_login_script'], 20);
  }

  /* ---------- Admin ---------- */

  public function admin_menu() {
    add_options_page('VK ID Login', 'VK ID Login', 'manage_options', 'vkid-login', [$this, 'settings_page']);
  }

  public function register_settings() {
    register_setting('vkid-login', self::OPTION_APP_ID);
    register_setting('vkid-login', self::OPTION_CLIENT_SECRET);
    register_setting('vkid-login', self::OPTION_REDIRECT_URL);
    register_setting('vkid-login', self::OPTION_SCOPE);
  }

  public function settings_page() { ?>
    <div class="wrap">
      <h1>VK ID Login</h1>
      <p>В <b>VK ID</b> (id.vk.com) включите нужные доступы и укажите <b>Authorized redirect URL</b> <i>в точности</i> как ниже — схема/домен/путь/слэш должны совпадать 1:1. Иначе VK вернёт <code>invalid or expired code</code>.</p>
      <form method="post" action="options.php">
        <?php settings_fields('vkid-login'); do_settings_sections('vkid-login'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="vkid_app_id">VK App ID</label></th>
            <td><input id="vkid_app_id" type="text" name="<?php echo esc_attr(self::OPTION_APP_ID); ?>" value="<?php echo esc_attr(get_option(self::OPTION_APP_ID, '')); ?>" class="regular-text"></td>
          </tr>
          <tr>
            <th scope="row"><label for="vkid_client_secret">VK Secure key (Client Secret)</label></th>
            <td><input id="vkid_client_secret" type="password" name="<?php echo esc_attr(self::OPTION_CLIENT_SECRET); ?>" value="<?php echo esc_attr(get_option(self::OPTION_CLIENT_SECRET, '')); ?>" class="regular-text">
              <p class="description">Для обмена кода на токен в потоке VK ID секрет обычно не нужен; пригодится для refresh токена.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="vkid_redirect_url">Authorized redirect URL</label></th>
            <td>
              <input id="vkid_redirect_url" type="url" name="<?php echo esc_attr(self::OPTION_REDIRECT_URL); ?>" value="<?php echo esc_attr(get_option(self::OPTION_REDIRECT_URL, home_url('/'))); ?>" class="regular-text">
              <p class="description">Должен совпадать с настройкой VK ID. Не используйте фрагменты (<code>#...</code>).</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="vkid_scope">Scope</label></th>
            <td>
              <input id="vkid_scope" type="text" name="<?php echo esc_attr(self::OPTION_SCOPE); ?>" value="<?php echo esc_attr(get_option(self::OPTION_SCOPE, 'email')); ?>" class="regular-text" placeholder="email,offline">
              <p class="description">Для e-mail укажите <code>email</code>. Телефон через OAuth не приходит — спросите на сайте после входа.</p>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <hr>
      <p>Вставьте на страницу шорткод: <code>[vkid_login]</code></p>
    </div>
  <?php }

  /* ---------- Shortcode (кнопка VK ID) ---------- */

  public function shortcode($atts = []) {
    $atts = shortcode_atts(['class' => ''], $atts, 'vkid_login');
    $extra_class = implode(' ', array_map('sanitize_html_class', preg_split('/\s+/', trim($atts['class']))));
    $classes = trim('vkid-login-trigger ' . $extra_class);

    return sprintf(
      '<button type="button" class="%s">%s</button>',
      esc_attr($classes),
      esc_html__('Войти через VK', 'vkid-login')
    );
  }

  /* ---------- Frontend OAuth behavior ---------- */

  public function render_login_script() {
  if (is_user_logged_in()) return;

  $appId      = (int) get_option(self::OPTION_APP_ID);
  $redirect   = trim(get_option(self::OPTION_REDIRECT_URL, home_url('/')));
  $scope      = trim(get_option(self::OPTION_SCOPE, 'email'));
  $endpoint   = esc_url_raw(rest_url('vkid/v1/login'));

  ob_start(); ?>
  <script>
  (function(){
    const sdkUrls = [
      'https://unpkg.com/@vkid/sdk@2/dist-sdk/umd/index.js',
      'https://cdn.jsdelivr.net/npm/@vkid/sdk@2/dist-sdk/umd/index.js'
    ];

    function loadScript(url) {
      return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.onload = resolve;
        script.onerror = () => reject(new Error('Не удалось загрузить ' + url));
        document.head.appendChild(script);
      });
    }

    async function getSdk() {
      if (window.VKIDSDK) return window.VKIDSDK;
      if (!window.vkidSdkLoading) {
        window.vkidSdkLoading = (async () => {
          for (const url of sdkUrls) {
            try {
              await loadScript(url);
              if (window.VKIDSDK) return window.VKIDSDK;
            } catch (error) {
              console.warn('VK ID SDK loading error', error);
            }
          }
          throw new Error('VK ID SDK недоступен. Проверьте блокировщик рекламы или доступ к CDN.');
        })();
      }
      return window.vkidSdkLoading;
    }

    // --- PKCE ---
    function b64url(buf){
      return btoa(String.fromCharCode.apply(null, new Uint8Array(buf)))
        .replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
    }

    // Web Crypto доступен только в secure context (HTTPS/localhost). Нужен для локальных HTTP-доменов.
    function sha256Fallback(ascii) {
      const rightRotate = (value, amount) => (value >>> amount) | (value << (32 - amount));
      const maxWord = Math.pow(2, 32);
      const words = [];
      const hash = [];
      const k = [];
      let primeCounter = 0;

      for (let candidate = 2; primeCounter < 64; candidate++) {
        let isPrime = true;
        for (let factor = 2; factor * factor <= candidate; factor++) {
          if (candidate % factor === 0) { isPrime = false; break; }
        }
        if (!isPrime) continue;
        if (primeCounter < 8) hash[primeCounter] = (Math.pow(candidate, 0.5) * maxWord) | 0;
        k[primeCounter++] = (Math.pow(candidate, 1 / 3) * maxWord) | 0;
      }

      const originalLength = ascii.length;
      ascii += '\x80';
      while (ascii.length % 64 !== 56) ascii += '\x00';
      for (let i = 0; i < ascii.length; i++) {
        words[i >> 2] |= ascii.charCodeAt(i) << ((3 - i % 4) * 8);
      }
      words[words.length] = (originalLength * 8 / maxWord) | 0;
      words[words.length] = originalLength * 8;

      for (let block = 0; block < words.length; block += 16) {
        const oldHash = hash.slice(0);
        const schedule = words.slice(block, block + 16);
        for (let i = 0; i < 64; i++) {
          const w15 = schedule[i - 15];
          const w2 = schedule[i - 2];
          const a = hash[0];
          const e = hash[4];
          const temp1 = hash[7]
            + (rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25))
            + ((e & hash[5]) ^ ((~e) & hash[6])) + k[i]
            + (schedule[i] = i < 16 ? schedule[i] : (
              schedule[i - 16]
              + (rightRotate(w15, 7) ^ rightRotate(w15, 18) ^ (w15 >>> 3))
              + schedule[i - 7]
              + (rightRotate(w2, 17) ^ rightRotate(w2, 19) ^ (w2 >>> 10))
            ) | 0);
          const temp2 = (rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22))
            + ((a & hash[1]) ^ (a & hash[2]) ^ (hash[1] & hash[2]));
          hash.unshift((temp1 + temp2) | 0);
          hash[4] = (hash[4] + temp1) | 0;
          hash.pop();
        }
        for (let i = 0; i < 8; i++) hash[i] = (hash[i] + oldHash[i]) | 0;
      }

      const bytes = new Uint8Array(32);
      for (let i = 0; i < 8; i++) {
        bytes[i * 4] = hash[i] >>> 24;
        bytes[i * 4 + 1] = hash[i] >>> 16;
        bytes[i * 4 + 2] = hash[i] >>> 8;
        bytes[i * 4 + 3] = hash[i];
      }
      return bytes;
    }

    async function pkcePair(){
      const rand = new Uint8Array(32); crypto.getRandomValues(rand);
      const verifier = b64url(rand);
      const digest = crypto.subtle && typeof crypto.subtle.digest === 'function'
        ? await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier))
        : sha256Fallback(verifier);
      const challenge = b64url(digest);
      return { verifier, challenge };
    }

    document.addEventListener('click', async function(e){
      const trigger = e.target.closest('.vkid-login-trigger');
      if (!trigger) return;
      e.preventDefault();
      if (trigger.dataset.vkidLoading === '1') return;

      trigger.dataset.vkidLoading = '1';
      trigger.setAttribute('aria-busy', 'true');

      try {
          const VKID = await getSdk();
          const { verifier, challenge } = await pkcePair();
          sessionStorage.setItem('vkid_code_verifier', verifier);

          VKID.Config.init({
            app: <?php echo $appId ?: 'null'; ?>,
            redirectUrl: <?php echo wp_json_encode($redirect); ?>,
            responseMode: VKID.ConfigResponseMode.Callback,
            source: VKID.ConfigSource.LOWCODE,
            scope: <?php echo wp_json_encode($scope); ?>,
            codeChallenge: challenge,
            codeChallengeMethod: 'S256'
          });

          const result = await VKID.Auth.login({provider: 'vkid'});
          if (result?.code && result?.device_id) {
            const cv = sessionStorage.getItem('vkid_code_verifier') || '';
            const r = await fetch(<?php echo wp_json_encode($endpoint); ?>, {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({
                code: result.code,
                device_id: result.device_id,
                code_verifier: cv
              })
            });
            const res = await r.json();
            if(res.ok){
              window.location.reload();
            } else {
              console.error('VKID login error', res);
              alert(res.vk_error_description || res.message || 'Ошибка входа через VK');
            }
          }
      } catch(err){
          console.error('VKID Auth error', err);
          alert(err && err.message ? err.message : 'VK ID: ошибка авторизации');
      } finally {
          delete trigger.dataset.vkidLoading;
          trigger.removeAttribute('aria-busy');
      }
    });
  })();
  </script>
  <?php
  echo ob_get_clean();
}


  /* ---------- REST API: обмен кода + создание/логин пользователя ---------- */

  public function register_routes() {
    register_rest_route('vkid/v1', '/login', [
      'methods'  => 'POST',
      'permission_callback' => '__return_true',
      'callback' => [$this, 'handle_login']
    ]);
  }

  public function handle_login(\WP_REST_Request $req) {
    $code         = sanitize_text_field( (string)$req->get_param('code') );
    $device_id    = sanitize_text_field( (string)$req->get_param('device_id') );
    $code_verifier= sanitize_text_field( (string)$req->get_param('code_verifier') );

    if (!$code || !$device_id || !$code_verifier) {
      return new \WP_REST_Response(['ok'=>false,'message'=>'No code/device_id/code_verifier'], 400);
    }

    // --- one-time lock по коду (анти-дубль), 3 минуты ---
    $lock_key = 'vkid_code_lock_' . md5($code);
    if (get_transient($lock_key)) {
      return new \WP_REST_Response(['ok'=>false,'message'=>'Code already used'], 409);
    }
    set_transient($lock_key, 1, 3 * MINUTE_IN_SECONDS);

    $client_id     = trim( (string) get_option(self::OPTION_APP_ID) );
    $client_secret = trim( (string) get_option(self::OPTION_CLIENT_SECRET) ); // на этом шаге можно не передавать
    $redirect_uri  = trim( (string) get_option(self::OPTION_REDIRECT_URL, home_url('/')) );
    $scope         = trim( (string) get_option(self::OPTION_SCOPE, 'email') );

    if (!$client_id || !$redirect_uri) {
      delete_transient($lock_key);
      return new \WP_REST_Response(['ok'=>false,'message'=>'VK config is incomplete'], 400);
    }

    // 1) Обмен кода на токены (VK ID, PKCE)
    $token_endpoint = 'https://id.vk.com/oauth2/auth';
    $body = [
      'grant_type'    => 'authorization_code',
      'code'          => $code,
      'redirect_uri'  => $redirect_uri,
      'client_id'     => $client_id,
      'device_id'     => $device_id,
      'code_verifier' => $code_verifier,
      // 'client_secret' => $client_secret, // как правило НЕ нужен на этом шаге; раскомментируйте при необходимости
    ];

    $resp = wp_remote_post($token_endpoint, [
      'timeout' => 20,
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'body'    => $body
    ]);

    if (is_wp_error($resp)) {
      delete_transient($lock_key);
      return new \WP_REST_Response(['ok'=>false,'message'=>$resp->get_error_message()], 500);
    }

    $status   = (int) wp_remote_retrieve_response_code($resp);
    $body_raw = (string) wp_remote_retrieve_body($resp);
    $tok      = json_decode($body_raw, true);

    if ($status !== 200 || (isset($tok['error']) && !isset($tok['access_token']))) {
      delete_transient($lock_key);
      return new \WP_REST_Response([
        'ok'                   => false,
        'message'              => 'VK token error',
        'vk_error'             => $tok['error'] ?? null,
        'vk_error_description' => $tok['error_description'] ?? null,
        'raw'                  => $body_raw
      ], 400);
    }

    $access_token = (string) ($tok['access_token'] ?? '');
    $refresh_token= (string) ($tok['refresh_token'] ?? '');
    $vk_user_id   = isset($tok['user_id']) ? (int)$tok['user_id'] : 0;
    $email        = isset($tok['email']) ? sanitize_email($tok['email']) : '';

    // 2) VK ID OAuth 2.1 отдаёт персональные данные отдельным user_info-запросом.
    // Token response не обязан содержать email даже при выданном scope=email.
    $first = $last = '';
    if ($access_token) {
      $info_resp = wp_remote_post('https://id.vk.com/oauth2/user_info', [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => [
          'access_token' => $access_token,
          'client_id'    => $client_id,
        ],
      ]);

      if (!is_wp_error($info_resp) && (int) wp_remote_retrieve_response_code($info_resp) === 200) {
        $info = json_decode((string) wp_remote_retrieve_body($info_resp), true);
        $profile = isset($info['user']) && is_array($info['user']) ? $info['user'] : $info;

        if (is_array($profile)) {
          $profile_email = sanitize_email((string) ($profile['email'] ?? ''));
          if ($profile_email && is_email($profile_email)) $email = $profile_email;
          if (!$vk_user_id && !empty($profile['user_id'])) $vk_user_id = (int) $profile['user_id'];
          $first = sanitize_text_field((string) ($profile['first_name'] ?? ''));
          $last  = sanitize_text_field((string) ($profile['last_name'] ?? ''));
        }
      }

      // Резерв для имени на случай временной недоступности user_info.
      $api_url = add_query_arg([
        'user_ids'     => $vk_user_id ?: '',
        'fields'       => 'first_name,last_name,photo_100',
        'access_token' => $access_token,
        'v'            => '5.199'
      ], 'https://api.vk.com/method/users.get');

      $u = wp_remote_get($api_url, ['timeout'=>15]);
      if (!is_wp_error($u)) {
        $ud = json_decode( (string) wp_remote_retrieve_body($u), true);
        if (!empty($ud['response'][0])) {
          if (!$first) $first = sanitize_text_field($ud['response'][0]['first_name'] ?? '');
          if (!$last)  $last  = sanitize_text_field($ud['response'][0]['last_name'] ?? '');
        }
      }
    }

    // 3) Подбираем username
    $username_base = $email ? current(explode('@', $email)) : ('vk_' . ($vk_user_id ?: wp_generate_password(6, false)));
    $username      = sanitize_user($username_base, true) ?: ('vk_' . wp_generate_password(6, false));
    $tmp = $username; $i=2;
    while (username_exists($tmp)) { $tmp = $username.$i; $i++; }
    $username = $tmp;

    // 4) Ищем уже существующего
    $user = null;
    if ($email) $user = get_user_by('email', $email);
    if (!$user && $vk_user_id) {
      $existing = get_users(['meta_key'=>'vk_user_id','meta_value'=>$vk_user_id,'number'=>1,'count_total'=>false]);
      if (!empty($existing)) $user = $existing[0];
    }

    // 5) Создаём при необходимости
    if (!$user) {
      $uid = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email ?: ($username.'@example.invalid'),
        'user_pass'  => wp_generate_password(20),
        'first_name' => $first,
        'last_name'  => $last,
        'role'       => get_option('default_role', 'subscriber'),
      ]);
      if (is_wp_error($uid)) {
        delete_transient($lock_key);
        return new \WP_REST_Response(['ok'=>false,'message'=>$uid->get_error_message()], 500);
      }
      add_user_meta($uid, 'vk_user_id', $vk_user_id, true);
      if ($refresh_token) update_user_meta($uid, 'vk_refresh_token', $refresh_token);
      $user = get_user_by('id', $uid);
    } else {
      if ($vk_user_id)   update_user_meta($user->ID, 'vk_user_id', $vk_user_id);
      if ($refresh_token) update_user_meta($user->ID, 'vk_refresh_token', $refresh_token);

      // Исправляем технический fallback-адрес после повторного входа с разрешённым email scope.
      $current_email = (string) $user->user_email;
      $has_fallback_email = substr($current_email, -16) === '@example.invalid';
      if ($email && is_email($email) && $has_fallback_email) {
        $email_owner = get_user_by('email', $email);
        if (!$email_owner || (int) $email_owner->ID === (int) $user->ID) {
          wp_update_user([
            'ID'         => $user->ID,
            'user_email' => $email,
          ]);
        }
      }
    }

    // 6) Авторизуем
    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);

    return new \WP_REST_Response(['ok'=>true], 200);
  }
}

new VKID_Login_Plugin();
