<?php
/**
 * Plugin Name:       Math Chat Render
 * Plugin URI:        https://github.com/leolimmabr/mathChatRender
 * Description:       Renderiza fórmulas matemáticas LaTeX (MathJax) em tempo real nas janelas de chat do AI Engine (MWAI Chatbot).
 * Version:           1.2.1
 * Author:            Leonardo Lima
 * Author URI:        https://github.com/leolimmabr
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       math-chat-render
 * Requires Plugins:  ai-engine
 */

// Se este arquivo for acessado diretamente, aborte.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Define o "apelido" do plugin para usar em hooks e handles.
 */
define( 'MCR_PREFIX', 'mcr' );
define( 'MCR_VERSION', '1.2.1' ); // Versão atualizada

/**
 * Registra página de opções e configurações no admin.
 */
function mcr_register_admin() {
  // Menu
  add_options_page(
    __( 'Math Chat Render', 'math-chat-render' ),
    __( 'Math Chat Render', 'math-chat-render' ),
    'manage_options',
    'math-chat-render',
    'mcr_options_page'
  );

  // Settings
  register_setting( 'mcr_options_group', 'mcr_options', 'mcr_sanitize_options' );

  add_settings_section( 'mcr_main_section', __( 'Configurações principais', 'math-chat-render' ), '__return_false', 'math-chat-render' );

  add_settings_field( 'mcr_load_mathjax', __( 'Carregar MathJax via CDN', 'math-chat-render' ), 'mcr_field_load_mathjax', 'math-chat-render', 'mcr_main_section' );
  add_settings_field( 'mcr_enable_delimiters', __( 'Delimitadores experimentais ( ( ) / [ ])', 'math-chat-render' ), 'mcr_field_enable_delimiters', 'math-chat-render', 'mcr_main_section' );
  add_settings_field( 'mcr_debug', __( 'Ativar debug (logs JS)', 'math-chat-render' ), 'mcr_field_debug', 'math-chat-render', 'mcr_main_section' );
}
add_action( 'admin_menu', 'mcr_register_admin' );

function mcr_sanitize_options( $input ) {
  $out = array();
  $out['load_mathjax'] = isset( $input['load_mathjax'] ) && $input['load_mathjax'] ? 1 : 0;
  $out['enable_delimiters'] = isset( $input['enable_delimiters'] ) && $input['enable_delimiters'] ? 1 : 0;
  $out['debug'] = isset( $input['debug'] ) && $input['debug'] ? 1 : 0;
  return $out;
}

function mcr_field_load_mathjax() {
  $opts = get_option( 'mcr_options', array() );
  $checked = ! empty( $opts['load_mathjax'] ) ? 'checked' : '';
  echo "<input type='checkbox' name='mcr_options[load_mathjax]' value='1' $checked />";
}

function mcr_field_enable_delimiters() {
  $opts = get_option( 'mcr_options', array() );
  $checked = ! empty( $opts['enable_delimiters'] ) ? 'checked' : '';
  echo "<input type='checkbox' name='mcr_options[enable_delimiters]' value='1' $checked />";
}

function mcr_field_debug() {
  $opts = get_option( 'mcr_options', array() );
  $checked = ! empty( $opts['debug'] ) ? 'checked' : '';
  echo "<input type='checkbox' name='mcr_options[debug]' value='1' $checked />";
}

function mcr_options_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }
  ?>
  <div class="wrap">
    <h1><?php esc_html_e( 'Math Chat Render', 'math-chat-render' ); ?></h1>
    <form method="post" action="options.php">
      <?php
      settings_fields( 'mcr_options_group' );
      do_settings_sections( 'math-chat-render' );
      submit_button();
      ?>
    </form>
  </div>
  <?php
}

/**
 * Função principal para enfileirar os scripts necessários.
 * v1.2.1 - Use handle próprio para o MathJax no enqueue e melhora da segurança da configuração inline.
 */
function mcr_enqueue_mathjax_scripts() {
    // don't enqueue in admin screens
    if ( is_admin() ) {
        return;
    }

  /**
   * Filtro público para controlar enfileiramento. Temas/plugins podem retornar false
   * para impedir que o MathJax seja carregado por este plugin.
   */
  if ( ! apply_filters( 'mcr_should_enqueue', true ) ) {
    return;
  }

  // Verifica opção admin: se o admin desativou o carregamento via CDN, respeitamos.
  $opts = get_option( 'mcr_options', array() );
  if ( isset( $opts['load_mathjax'] ) && ! $opts['load_mathjax'] ) {
    // Ainda assim, injeta a configuração global mínima para o handler (debug/config)
    $config = array(
      'debug' => ! empty( $opts['debug'] ) ? 1 : 0,
      'enable_delimiters' => ! empty( $opts['enable_delimiters'] ) ? 1 : 0,
    );
    $config_script = 'globalThis.mcrConfig = ' . wp_json_encode( $config ) . ';';
    // Injeta antes do nosso handler (pode não estar enfileirado, mas é seguro)
    wp_register_script( MCR_PREFIX . '-handler-config', '' );
    wp_add_inline_script( MCR_PREFIX . '-handler-config', $config_script, 'before' );
    return;
  }

    // 2. Adicionar o script de configuração inline (nowdoc - seguro)
    $mathjax_config = <<<'MATHJAX'
window.MathJax = {
  tex: {
    inlineMath: [
      ['$', '$'],
      ['\\(', '\\)'],   // suporte padrão \( ... \)
      ['(', ')']            // suporte experimental para ( ... )
    ],
    displayMath: [
      ['$$', '$$'],
      ['\\[', '\\]'],   // suporte padrão \[ ... \]
      ['[', ']']            // suporte experimental para [ ... ]
    ]
  },
  svg: {
    fontCache: 'global'
  }
};
MATHJAX;

    // Se outro plugin/tema já registou/enfileirou um handle conhecido do MathJax,
    // usamos esse handle e apenas injetamos a configuração inline nele.
    $known_handles = array( 'mcr-mathjax', 'mathjax-core' );
    $use_handle = null;
    foreach ( $known_handles as $h ) {
        if ( wp_script_is( $h, 'registered' ) || wp_script_is( $h, 'enqueued' ) ) {
            $use_handle = $h;
            break;
        }
    }

    if ( ! $use_handle ) {
        // Enfileira nosso próprio handle
        wp_enqueue_script(
            'mcr-mathjax',
            'https://cdn.jsdelivr.net/npm/mathjax@4/tex-mml-chtml.js', // Caminho CDN v4
            array(),
            '4.0.0', // Versão do CDN
            true
        );
        $use_handle = 'mcr-mathjax';
    }

  // Se desejar adicionar SRI (subresource integrity), o site pode fornecer o hash
  // via filtro 'mcr_mathjax_sri'. Por padrão deixamos em branco.
  $sri = apply_filters( 'mcr_mathjax_sri', '' );
  if ( $sri ) {
    wp_script_add_data( $use_handle, 'integrity', $sri );
    wp_script_add_data( $use_handle, 'crossorigin', 'anonymous' );
  }

    // Injeta a configuração antes do handle definido
    wp_add_inline_script( $use_handle, $mathjax_config, 'before' );

    // 3. Enfileirar o NOSSO script "observador" (handler)
    // Este ficheiro DEVE estar em /js/mwai-mathjax-handler.js
  wp_enqueue_script(
    MCR_PREFIX . '-handler',
    plugin_dir_url( __FILE__ ) . 'js/mwai-mathjax-handler.js',
    array( $use_handle ),
    MCR_VERSION,
    true
  );

  // Se estamos servindo um bundle ESM (build do src/), marcamos o script como module
  // para que browsers modernos carreguem o bundle com top-level await transpiled.
  wp_script_add_data( MCR_PREFIX . '-handler', 'type', 'module' );

  // Injeta configuração pública (debug / delimitadores) para o handler JS
  $opts = get_option( 'mcr_options', array() );
  $config = array(
    'debug' => ! empty( $opts['debug'] ) ? 1 : 0,
    'enable_delimiters' => ! empty( $opts['enable_delimiters'] ) ? 1 : 0,
  );
  $config_script = 'globalThis.mcrConfig = ' . wp_json_encode( $config ) . ';';
  wp_add_inline_script( MCR_PREFIX . '-handler', $config_script, 'before' );

  // Fallback local: se o CDN falhar, tentamos carregar um arquivo local em /vendor/
  // NOTA: O site deve colocar uma cópia do MathJax v4 em vendor/mathjax/tex-mml-chtml.js
  $local_url = plugin_dir_url( __FILE__ ) . 'vendor/mathjax/tex-mml-chtml.js';
  $fallback_js = "(function(){ setTimeout(function(){ if (typeof globalThis.MathJax === 'undefined') { var s = document.createElement('script'); s.src='" . esc_url( $local_url ) . "'; s.async=true; document.head.appendChild(s); } }, 3000); })();";
  wp_add_inline_script( $use_handle, $fallback_js, 'after' );
}

/**
 * Adiciona nossa função ao hook padrão do WordPress para scripts do front-end.
 */
add_action( 'wp_enqueue_scripts', 'mcr_enqueue_mathjax_scripts' );


