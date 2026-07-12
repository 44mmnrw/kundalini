<?php
/**
 * Plugin Name: VK ID Login (Custom)
 * Description: Вход/регистрация в WordPress через VK ID Web SDK (PKCE): кнопка, обмен кода на токен, создание/логин пользователя.
 * Version: 1.0.0
 * Author: AxeCode.Tech
 */

if (!defined('ABSPATH')) exit;

class VKID_Login_Plugin {
  const TOKEN_ENDPOINT       = 'https://id.vk.com/oauth2/auth';
  const USER_INFO_ENDPOINT   = 'https://id.vk.com/oauth2/user_info';
  const VK_API_ENDPOINT      = 'https://api.vk.com/method/users.get';
  const OPTION_APP_ID         = 'vkid_app_id';
  const OPTION_CLIENT_SECRET  = 'vkid_client_secret'; // для refresh потока; на обмен кода не обязателен
  const OPTION_REDIRECT_URL   = 'vkid_redirect_url';  // ДОЛЖЕН совпадать 1:1 с VK ID (без фрагмента #)

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
  $scope      = 'email';
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

  private function error_response($message, $status, $extra = []) {
    return new \WP_REST_Response(array_merge(['ok' => false, 'message' => $message], $extra), $status);
  }

  private function exchange_code($code, $device_id, $code_verifier, $client_id, $redirect_uri) {
    $response = wp_remote_post(self::TOKEN_ENDPOINT, [
      'timeout' => 20,
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'body'    => [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $redirect_uri,
        'client_id'     => $client_id,
        'device_id'     => $device_id,
        'code_verifier' => $code_verifier,
      ],
    ]);

    if (is_wp_error($response)) return $response;

    $data = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($data)) $data = [];
    $status = (int) wp_remote_retrieve_response_code($response);

    if ($status !== 200 || empty($data['access_token'])) {
      return new \WP_Error('vkid_token_error', 'VK token error', [
        'status'                => 400,
        'vk_error'              => $data['error'] ?? null,
        'vk_error_description'  => $data['error_description'] ?? null,
      ]);
    }

    return $data;
  }

  private function fetch_profile($access_token, $client_id, $vk_user_id) {
    $profile = ['email' => '', 'first_name' => '', 'last_name' => '', 'user_id' => $vk_user_id];
    $response = wp_remote_post(self::USER_INFO_ENDPOINT, [
      'timeout' => 15,
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'body'    => ['access_token' => $access_token, 'client_id' => $client_id],
    ]);

    if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
      $data = json_decode((string) wp_remote_retrieve_body($response), true);
      $user = isset($data['user']) && is_array($data['user']) ? $data['user'] : $data;
      if (is_array($user)) {
        $email = sanitize_email((string) ($user['email'] ?? ''));
        $profile['email'] = is_email($email) ? $email : '';
        $profile['user_id'] = (int) ($user['user_id'] ?? $profile['user_id']);
        $profile['first_name'] = sanitize_text_field((string) ($user['first_name'] ?? ''));
        $profile['last_name'] = sanitize_text_field((string) ($user['last_name'] ?? ''));
      }
    }

    if ($profile['first_name'] && $profile['last_name']) return $profile;

    $api_url = add_query_arg([
      'user_ids'     => $profile['user_id'] ?: '',
      'fields'       => 'first_name,last_name',
      'access_token' => $access_token,
      'v'            => '5.199',
    ], self::VK_API_ENDPOINT);
    $response = wp_remote_get($api_url, ['timeout' => 15]);
    if (!is_wp_error($response)) {
      $data = json_decode((string) wp_remote_retrieve_body($response), true);
      $user = $data['response'][0] ?? [];
      if (!$profile['first_name']) $profile['first_name'] = sanitize_text_field($user['first_name'] ?? '');
      if (!$profile['last_name']) $profile['last_name'] = sanitize_text_field($user['last_name'] ?? '');
    }

    return $profile;
  }

  private function find_user($email, $vk_user_id) {
    $user = $email ? get_user_by('email', $email) : false;
    if ($user || !$vk_user_id) return $user;

    $users = get_users([
      'meta_key'   => 'vk_user_id',
      'meta_value' => $vk_user_id,
      'number'     => 1,
      'count_total'=> false,
    ]);
    return $users ? $users[0] : false;
  }

  private function unique_username($email, $vk_user_id) {
    $base = $email ? strstr($email, '@', true) : 'vk_' . $vk_user_id;
    $base = sanitize_user($base, true) ?: 'vk_' . $vk_user_id;
    $username = $base;
    for ($suffix = 2; username_exists($username); $suffix++) $username = $base . $suffix;
    return $username;
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

    $client_id    = trim((string) get_option(self::OPTION_APP_ID));
    $redirect_uri = trim((string) get_option(self::OPTION_REDIRECT_URL, home_url('/')));

    if (!$client_id || !$redirect_uri) {
      delete_transient($lock_key);
      return $this->error_response('VK config is incomplete', 400);
    }

    $tok = $this->exchange_code($code, $device_id, $code_verifier, $client_id, $redirect_uri);
    if (is_wp_error($tok)) {
      delete_transient($lock_key);
      $data = $tok->get_error_data();
      if (!is_array($data)) $data = [];
      return $this->error_response($tok->get_error_message(), (int) ($data['status'] ?? 500), [
        'vk_error'             => $data['vk_error'] ?? null,
        'vk_error_description' => $data['vk_error_description'] ?? null,
      ]);
    }

    $access_token = (string) ($tok['access_token'] ?? '');
    $refresh_token= (string) ($tok['refresh_token'] ?? '');
    $vk_user_id   = isset($tok['user_id']) ? (int)$tok['user_id'] : 0;
    $email        = isset($tok['email']) ? sanitize_email($tok['email']) : '';
    if (!$email || !is_email($email)) $email = '';

    $profile = $this->fetch_profile($access_token, $client_id, $vk_user_id);
    if ($profile['email']) $email = $profile['email'];
    $vk_user_id = $profile['user_id'];
    $first = $profile['first_name'];
    $last = $profile['last_name'];

    if (!$vk_user_id) {
      delete_transient($lock_key);
      return $this->error_response('VK user ID is missing', 400);
    }

    $user = $this->find_user($email, $vk_user_id);

    if (!$user) {
      $uid = wp_insert_user([
        'user_login' => $this->unique_username($email, $vk_user_id),
        // Do not invent an address: WordPress accepts an empty user_email and
        // must not send account mail to a technical @example.invalid value.
        'user_email' => $email,
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
      if ($email && is_email($email) && ($has_fallback_email || !$current_email)) {
        $email_owner = get_user_by('email', $email);
        if (!$email_owner || (int) $email_owner->ID === (int) $user->ID) {
          wp_update_user([
            'ID'         => $user->ID,
            'user_email' => $email,
          ]);
        }
      } elseif ($has_fallback_email) {
        // Clean up accounts created by older plugin versions. A real address
        // will be filled on a later login as soon as VK grants email access.
        wp_update_user([
          'ID'         => $user->ID,
          'user_email' => '',
        ]);
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
