<?php
/**
 * Plugin Name:       Math Chat Render
 * Plugin URI:        (Coloque o URL do seu site ou repositório aqui)
 * Description:       Renderiza fórmulas matemáticas LaTeX (MathJax) em tempo real nas janelas de chat do AI Engine (MWAI Chatbot).
 * Version:           1.1.0
 * Author:            (O Seu Nome Aqui)
 * Author URI:        (O Seu Site Aqui)
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       math-chat-render
 * Requires Plugins:  ai-engine
 */

// Se este arquivo for acessado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define o "apelido" do plugin para usar em hooks e handles.
 */
define( 'MCR_PREFIX', 'mcr' );
define( 'MCR_VERSION', '1.1.0' ); // Versão de produção

/**
 * Função principal para enfileirar os scripts necessários.
 * v1.0.3 - Adicionado suporte para [ ... ] e ( ... )
 */
function mcr_enqueue_mathjax_scripts() {

    // 1. Enfileirar o script principal do MathJax (v3)
    wp_enqueue_script(
        'mathjax-core', 
        'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js',
        array(), 
        '3.2.2', 
        true 
    );

    // 2. Adicionar o script de configuração inline
    // Suporta $, $$, \(), \[], \\(), \\[...], [ ... ] e ( ... )
    $mathjax_config = "
    window.MathJax = {
      tex: {
        inlineMath: [
          ['$', '$'],           
          ['\\\\(', '\\\\)'],     
          ['\\\\\\\\(', '\\\\\\\\)'],
          ['(', ')'] // Suporte para parênteses simples
        ],
        displayMath: [
          ['$$', '$$'],         
          ['\\\\[', '\\\\]'],     
          ['\\\\\\\\\[', '\\\\\\\\\]'],
          ['[', ']'] // Suporte para colchetes simples
        ]
      },
      svg: {
        fontCache: 'global'
      }
    };
    ";
    wp_add_inline_script( 'mathjax-core', $mathjax_config, 'before' );

    // 3. Enfileirar o NOSSO script "observador" (handler)
    wp_enqueue_script(
        MCR_PREFIX . '-handler',
        plugin_dir_url( __FILE__ ) . 'js/mwai-mathjax-handler.js',
        array( 'mathjax-core' ), 
        MCR_VERSION,
        true
    );
}

/**
 * Adiciona nossa função ao hook padrão do WordPress para scripts do front-end.
 */
add_action( 'wp_enqueue_scripts', 'mcr_enqueue_mathjax_scripts' );

?>

