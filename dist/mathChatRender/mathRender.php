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

// Administração removida: configurações via UI foram suprimidas para evitar
// inconsistências de runtime. Ainda assim expomos filtros PHP que permitem
// customização por desenvolvedores (ex.: `add_filter('mcr_load_mathjax', ...)`).


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
  // Defaults de configuração (podem ser sobrescritas por filtro `mcr_config`)
  $defaults = array(
    'debug' => 0,
    'enable_delimiters' => 1,
  );
  $config = apply_filters( 'mcr_config', $defaults );
  $config_script = 'globalThis.mcrConfig = ' . wp_json_encode( $config ) . ';';

  // Se o site optar por não carregar o MathJax via este plugin, retorne false
  // pelo filtro `mcr_load_mathjax`. Ainda assim, enfileiramos o handler e
  // injetamos a configuração para que outros carregadores possam inicializar.
  $load_mathjax = apply_filters( 'mcr_load_mathjax', true );

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

  if ( $load_mathjax ) {
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
  }

  // Se desejar adicionar SRI (subresource integrity), o site pode fornecer o hash
  // via filtro 'mcr_mathjax_sri'. Por padrão deixamos em branco.
  $sri = apply_filters( 'mcr_mathjax_sri', '' );
  if ( $load_mathjax && $sri ) {
    wp_script_add_data( $use_handle, 'integrity', $sri );
    wp_script_add_data( $use_handle, 'crossorigin', 'anonymous' );
  }

  // Injeta a configuração antes do handle de MathJax se estiver sendo enfileirado,
  // caso contrário injetamos antes do nosso handler para que quem carregar o
  // MathJax posteriormente possa usar a mesma configuração.
  if ( $load_mathjax && $use_handle ) {
    wp_add_inline_script( $use_handle, $mathjax_config, 'before' );
  }

  // 3. Enfileirar o NOSSO script "observador" (handler)
  // Este ficheiro DEVE estar em /js/mwai-mathjax-handler.js
  $handler_deps = array();
  if ( $load_mathjax && $use_handle ) {
    $handler_deps[] = $use_handle;
  }

  wp_enqueue_script(
    MCR_PREFIX . '-handler',
    plugin_dir_url( __FILE__ ) . 'js/mwai-mathjax-handler.js',
    $handler_deps,
    MCR_VERSION,
    true
  );

  // O handler é compatível com scripts clássicos; removemos o atributo 'module'
  // para garantir compatibilidade com temas/instalações que não usam modules.

  // Injeta configuração pública (debug / delimitadores) para o handler JS
  wp_add_inline_script( MCR_PREFIX . '-handler', $config_script, 'before' );

  // Fallback local: se o CDN falhar, tentamos carregar um arquivo local em /vendor/
  // NOTA: O site deve colocar uma cópia do MathJax v4 em vendor/mathjax/tex-mml-chtml.js
  if ( $load_mathjax && $use_handle ) {
    $local_url = plugin_dir_url( __FILE__ ) . 'vendor/mathjax/tex-mml-chtml.js';
    $fallback_js = "(function(){ setTimeout(function(){ if (typeof globalThis.MathJax === 'undefined') { var s = document.createElement('script'); s.src='" . esc_url( $local_url ) . "'; s.async=true; document.head.appendChild(s); } }, 3000); })();";
    wp_add_inline_script( $use_handle, $fallback_js, 'after' );
  }
}

/**
 * Adiciona nossa função ao hook padrão do WordPress para scripts do front-end.
 */
add_action( 'wp_enqueue_scripts', 'mcr_enqueue_mathjax_scripts' );


